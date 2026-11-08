<?php

/**
 * Local stand-in for Google's OAuth and API hosts. One process, one state file.
 * Not a production server. Tests point config URLs here.
 */

$stateFile = getenv('GOOGLE_FAKE_STATE') ?: '';

if ($stateFile === '' || ! is_file($stateFile)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'fake_google_state_missing']);
    return;
}

$expectedId = getenv('GOOGLE_FAKE_CLIENT_ID') ?: '';
$expectedSecret = getenv('GOOGLE_FAKE_CLIENT_SECRET') ?: '';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$raw = file_get_contents('php://input') ?: '';
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$parsedBody = [];

if (str_contains($contentType, 'application/json')) {
    $parsedBody = json_decode($raw, true) ?: [];
} elseif ($raw !== '') {
    parse_str($raw, $parsedBody);
}

$auth = $_SERVER['HTTP_AUTHORIZATION']
    ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
    ?? '';

$mutate = function (callable $fn) use ($stateFile) {
    $fh = fopen($stateFile, 'c+');
    if ($fh === false) {
        return null;
    }
    flock($fh, LOCK_EX);
    $contents = stream_get_contents($fh);
    $state = json_decode($contents ?: '{}', true);
    if (! is_array($state)) {
        $state = [];
    }
    $result = $fn($state);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($state));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);

    return $result;
};

$record = function (array $state, array $extra = []) use ($method, $path, $auth, $parsedBody): array {
    $state['calls'][] = array_merge([
        'method' => $method,
        'path' => $path,
        'query' => $_GET,
        'authorization' => $auth,
        'body' => $parsedBody,
    ], $extra);
    if (count($state['calls']) > 300) {
        $state['calls'] = array_slice($state['calls'], -300);
    }

    return $state;
};

$json = function (int $status, array $body): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($body);
};

$bearer = '';
if (preg_match('/Bearer\s+(\S+)/', $auth, $m)) {
    $bearer = $m[1];
}

if ($path === '/o/oauth2/v2/auth' && $method === 'GET') {
    $mutate(function (array &$state) use ($record, $expectedId, $json) {
        $state = $record($state);
        if (($_GET['client_id'] ?? '') !== $expectedId || $expectedId === '') {
            $json(400, ['error' => 'invalid_client']);

            return null;
        }
        $requested = array_values(array_filter(preg_split('/\s+/', trim((string) ($_GET['scope'] ?? ''))) ?: []));
        $include = ($_GET['include_granted_scopes'] ?? '') === 'true';
        $previous = (! empty($state['account']['revoked'])) ? [] : ($state['account']['scopes'] ?? []);
        $effective = $include ? array_values(array_unique(array_merge($previous, $requested))) : $requested;
        $code = 'g-code-'.bin2hex(random_bytes(8));
        $state['codes'][$code] = [
            'scopes' => $effective,
            'redirect_uri' => (string) ($_GET['redirect_uri'] ?? ''),
        ];
        $redirect = ($_GET['redirect_uri'] ?? '').(str_contains((string) ($_GET['redirect_uri'] ?? ''), '?') ? '&' : '?').http_build_query([
            'code' => $code,
            'state' => (string) ($_GET['state'] ?? ''),
        ]);
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($accept, 'application/json')) {
            $json(200, [
                'code' => $code,
                'state' => (string) ($_GET['state'] ?? ''),
                'redirect' => $redirect,
                'scope' => implode(' ', $effective),
            ]);

            return null;
        }
        http_response_code(302);
        header('Location: '.$redirect);
        echo '';

        return null;
    });

    return;
}

if ($path === '/token' && $method === 'POST') {
    $mutate(function (array &$state) use ($record, $expectedId, $expectedSecret, $parsedBody, $json) {
        $state = $record($state);
        if (($parsedBody['client_id'] ?? '') !== $expectedId || ($parsedBody['client_secret'] ?? '') !== $expectedSecret) {
            $json(401, ['error' => 'invalid_client']);

            return null;
        }
        $mode = $state['modes']['token'] ?? null;
        if (($parsedBody['grant_type'] ?? '') === 'refresh_token') {
            $mode = $state['modes']['refresh'] ?? $mode;
        }
        if ($mode === 'invalid_grant' || ! empty($state['account']['revoked'])) {
            $json(400, ['error' => 'invalid_grant', 'error_description' => 'Token has been expired or revoked.']);

            return null;
        }
        if ($mode === 'error') {
            $json(500, ['error' => ['code' => 500, 'message' => 'backend error', 'status' => 'INTERNAL']]);

            return null;
        }
        $grant = (string) ($parsedBody['grant_type'] ?? '');
        if ($grant === 'authorization_code') {
            $code = (string) ($parsedBody['code'] ?? '');
            $issued = $state['codes'][$code] ?? null;
            if ($issued === null || ($parsedBody['redirect_uri'] ?? '') !== ($issued['redirect_uri'] ?? null)) {
                $json(400, ['error' => 'invalid_grant', 'error_description' => 'Bad code.']);

                return null;
            }
            unset($state['codes'][$code]);
            $scopes = $issued['scopes'];
        } elseif ($grant === 'refresh_token') {
            if (($parsedBody['refresh_token'] ?? '') === '' || ($parsedBody['refresh_token'] ?? '') !== ($state['refresh'] ?? null)) {
                $json(400, ['error' => 'invalid_grant', 'error_description' => 'Bad refresh token.']);

                return null;
            }
            $scopes = $state['account']['scopes'] ?? [];
        } else {
            $json(400, ['error' => 'unsupported_grant_type']);

            return null;
        }
        $access = 'g-access-'.bin2hex(random_bytes(8));
        $refresh = $state['refresh'] ?: ('g-refresh-'.bin2hex(random_bytes(8)));
        $state['refresh'] = $refresh;
        $state['account']['scopes'] = $scopes;
        $state['account']['revoked'] = false;
        $state['tokens'][$access] = [
            'sub' => $state['account']['sub'],
            'email' => $state['account']['email'],
        ];
        $json(200, [
            'access_token' => $access,
            'expires_in' => 3600,
            'refresh_token' => $refresh,
            'scope' => implode(' ', $scopes),
            'token_type' => 'Bearer',
        ]);

        return null;
    });

    return;
}

if ($path === '/revoke' && $method === 'POST') {
    $mutate(function (array &$state) use ($record, $parsedBody, $json) {
        $state = $record($state);
        $token = (string) ($parsedBody['token'] ?? '');
        if ($token !== '' && ($token === ($state['refresh'] ?? null) || isset($state['tokens'][$token]))) {
            $state['account']['revoked'] = true;
            $state['account']['scopes'] = [];
            $state['tokens'] = [];
            $state['refresh'] = null;
        }
        $json(200, ['revoked' => true]);

        return null;
    });

    return;
}

if ($path === '/oauth2/v2/userinfo' && $method === 'GET') {
    $mutate(function (array &$state) use ($record, $bearer, $json) {
        $state = $record($state);
        if (($state['modes']['userinfo'] ?? null) === '500') {
            $json(500, ['error' => ['code' => 500, 'message' => 'backend error', 'status' => 'INTERNAL']]);

            return null;
        }
        $token = $state['tokens'][$bearer] ?? null;
        if ($token === null || ! empty($state['account']['revoked'])) {
            $json(401, ['error' => ['code' => 401, 'message' => 'Invalid Credentials', 'status' => 'UNAUTHENTICATED']]);

            return null;
        }
        $json(200, ['id' => $token['sub'], 'email' => $token['email']]);

        return null;
    });

    return;
}

$json(404, ['error' => ['code' => 404, 'message' => 'No fake Google handler for '.$path, 'status' => 'NOT_FOUND']]);
