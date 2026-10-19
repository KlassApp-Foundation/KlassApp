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
} elseif ($raw !== '' && ! str_contains($contentType, 'multipart/')) {
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
    $encoded = json_encode($state, JSON_INVALID_UTF8_SUBSTITUTE);
    fwrite($fh, $encoded === false ? '{}' : $encoded);
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

if (preg_match('#^/v4/spreadsheets(?:/([^/]+)(?:/values/(.+))?)?$#', $path, $sheetMatch)) {
    $mutate(function (array &$state) use ($record, $bearer, $method, $parsedBody, $json, $sheetMatch) {
        $state = $record($state);
        if (($state['modes']['sheets'] ?? null) === '500') {
            $json(500, ['error' => ['code' => 500, 'message' => 'backend error', 'status' => 'INTERNAL']]);

            return null;
        }
        if ($bearer === '' || ! isset($state['tokens'][$bearer]) || ! empty($state['account']['revoked'])) {
            $json(401, ['error' => ['code' => 401, 'message' => 'Invalid Credentials', 'status' => 'UNAUTHENTICATED']]);

            return null;
        }
        $id = isset($sheetMatch[1]) ? rawurldecode($sheetMatch[1]) : null;
        $range = isset($sheetMatch[2]) ? rawurldecode($sheetMatch[2]) : null;
        if ($method === 'POST' && $id === null) {
            $newId = 'sheet-'.bin2hex(random_bytes(4));
            $tabs = [];
            foreach ($parsedBody['sheets'] ?? [] as $tab) {
                $title = (string) ($tab['properties']['title'] ?? 'Sheet1');
                $tabs[$title] = [];
            }
            $state['sheets'][$newId] = [
                'title' => (string) ($parsedBody['properties']['title'] ?? 'Untitled'),
                'tabs' => $tabs,
            ];
            $json(200, [
                'spreadsheetId' => $newId,
                'spreadsheetUrl' => 'https://docs.google.com/spreadsheets/d/'.$newId,
            ]);

            return null;
        }
        if ($id === null || ! isset($state['sheets'][$id])) {
            $json(404, ['error' => ['code' => 404, 'message' => 'Spreadsheet not found', 'status' => 'NOT_FOUND']]);

            return null;
        }
        $tab = 'Marks';
        if (is_string($range) && preg_match("/^'([^']+)'!/", $range, $rm)) {
            $tab = $rm[1];
        } elseif (is_string($range) && preg_match('/^([^!]+)!/', $range, $rm)) {
            $tab = $rm[1];
        }
        if ($method === 'PUT' && $range !== null) {
            $state['sheets'][$id]['tabs'][$tab] = $parsedBody['values'] ?? [];
            $json(200, ['spreadsheetId' => $id, 'updatedRange' => $range, 'updatedRows' => count($parsedBody['values'] ?? [])]);

            return null;
        }
        if ($method === 'GET' && $range !== null) {
            $json(200, [
                'range' => $range,
                'majorDimension' => 'ROWS',
                'values' => $state['sheets'][$id]['tabs'][$tab] ?? [],
            ]);

            return null;
        }
        if ($method === 'GET') {
            $json(200, $state['sheets'][$id]);

            return null;
        }
        $json(405, ['error' => ['code' => 405, 'message' => 'Method not allowed', 'status' => 'METHOD_NOT_ALLOWED']]);

        return null;
    });

    return;
}

if (! function_exists('drive_store_file')) {
    /**
     * @param  array<string, mixed>  $state
     * @param  array{name: string, mimeType: string, parents: list<string>, content: ?string}  $file
     * @return array<string, mixed>
     */
    function drive_store_file(array &$state, array $file): array
    {
        $id = 'drv-'.$state['drive']['next'];
        $state['drive']['next']++;
        $stored = [
            'id' => $id,
            'name' => $file['name'],
            'mimeType' => $file['mimeType'],
            'parents' => $file['parents'],
            'content' => $file['content'],
        ];
        $state['drive']['files'][] = $stored;

        return [
            'id' => $id,
            'name' => $stored['name'],
            'mimeType' => $stored['mimeType'],
            'parents' => $stored['parents'],
            'webViewLink' => 'https://drive.google.com/file/d/'.$id.'/view',
        ];
    }
}

if (! function_exists('drive_multipart_parts')) {
    /**
     * @return list<array{type: string, body: string}>
     */
    function drive_multipart_parts(string $raw, string $contentType): array
    {
        if (! preg_match('/boundary=([^;]+)/', $contentType, $match)) {
            return [];
        }
        $boundary = trim($match[1], " \t\"'");
        $chunks = explode('--'.$boundary, $raw);
        $parts = [];
        foreach ($chunks as $chunk) {
            $chunk = ltrim($chunk, "\r\n");
            if ($chunk === '' || str_starts_with($chunk, '--')) {
                continue;
            }
            [$headers, $body] = array_pad(preg_split("/\r\n\r\n/", $chunk, 2), 2, '');
            $type = 'application/octet-stream';
            if (preg_match('/Content-Type:\s*([^\r\n;]+)/i', $headers, $typeMatch)) {
                $type = strtolower(trim($typeMatch[1]));
            }
            $parts[] = ['type' => $type, 'body' => rtrim($body, "\r\n")];
        }

        return $parts;
    }
}

if ($path === '/drive/v3/files' || $path === '/upload/drive/v3/files') {
    $mutate(function (array &$state) use ($record, $bearer, $method, $path, $raw, $contentType, $parsedBody, $json) {
        $state = $record($state);
        if (($state['modes']['drive'] ?? null) === '500') {
            $json(500, ['error' => ['code' => 500, 'message' => 'backend error', 'status' => 'INTERNAL']]);

            return null;
        }
        if ($bearer === '' || ! isset($state['tokens'][$bearer]) || ! empty($state['account']['revoked'])) {
            $json(401, ['error' => ['code' => 401, 'message' => 'Invalid Credentials', 'status' => 'UNAUTHENTICATED']]);

            return null;
        }
        if (! isset($state['drive']['files']) || ! is_array($state['drive']['files'])) {
            $state['drive'] = ['files' => [], 'next' => 1];
        }

        if ($path === '/drive/v3/files' && $method === 'GET') {
            $q = isset($_GET['q']) ? (string) $_GET['q'] : '';
            $name = null;
            $parent = null;
            if (preg_match("/name = '((?:\\\\'|[^'])*)'/", $q, $match)) {
                $name = str_replace(["\\'", '\\\\'], ["'", '\\'], $match[1]);
            }
            if (preg_match("/'([^']+)' in parents/", $q, $match)) {
                $parent = $match[1];
            }
            $files = [];
            foreach ($state['drive']['files'] as $file) {
                if (($file['mimeType'] ?? '') !== 'application/vnd.google-apps.folder') {
                    continue;
                }
                if ($name !== null && ($file['name'] ?? '') !== $name) {
                    continue;
                }
                $parents = $file['parents'] ?? [];
                if ($parent !== null && ! in_array($parent, $parents, true)) {
                    continue;
                }
                $files[] = [
                    'id' => $file['id'],
                    'name' => $file['name'],
                    'mimeType' => $file['mimeType'],
                    'parents' => $parents,
                ];
            }
            $json(200, ['files' => $files]);

            return null;
        }

        if ($path === '/drive/v3/files' && $method === 'POST') {
            $created = drive_store_file($state, [
                'name' => (string) ($parsedBody['name'] ?? ''),
                'mimeType' => (string) ($parsedBody['mimeType'] ?? 'application/octet-stream'),
                'parents' => array_values($parsedBody['parents'] ?? ['root']),
                'content' => null,
            ]);
            $json(200, $created);

            return null;
        }

        if ($path === '/upload/drive/v3/files' && $method === 'POST') {
            $parts = drive_multipart_parts($raw, $contentType);
            $meta = [];
            $bytes = '';
            foreach ($parts as $part) {
                if (str_contains($part['type'], 'json')) {
                    $decoded = json_decode($part['body'], true);
                    $meta = is_array($decoded) ? $decoded : [];
                } else {
                    $bytes = $part['body'];
                }
            }
            if (($meta['name'] ?? '') === '' || $bytes === '') {
                $json(400, ['error' => ['code' => 400, 'message' => 'Upload needs a name and a file body', 'status' => 'INVALID_ARGUMENT']]);

                return null;
            }
            $created = drive_store_file($state, [
                'name' => (string) $meta['name'],
                'mimeType' => (string) ($meta['mimeType'] ?? 'application/pdf'),
                'parents' => array_values($meta['parents'] ?? ['root']),
                'content' => base64_encode($bytes),
            ]);
            $json(200, $created);

            return null;
        }

        $json(405, ['error' => ['code' => 405, 'message' => 'Method not allowed', 'status' => 'METHOD_NOT_ALLOWED']]);

        return null;
    });

    return;
}

if (preg_match('#^/v1/courses(?:/([^/]+)/(students|courseWork))?$#', $path, $classMatch)) {
    $mutate(function (array &$state) use ($record, $bearer, $method, $json, $classMatch) {
        $state = $record($state);
        if (($state['modes']['classroom'] ?? null) === '500') {
            $json(500, ['error' => ['code' => 500, 'message' => 'backend error', 'status' => 'INTERNAL']]);

            return null;
        }
        if ($bearer === '' || ! isset($state['tokens'][$bearer]) || ! empty($state['account']['revoked'])) {
            $json(401, ['error' => ['code' => 401, 'message' => 'Invalid Credentials', 'status' => 'UNAUTHENTICATED']]);

            return null;
        }
        if ($method !== 'GET') {
            $json(405, ['error' => ['code' => 405, 'message' => 'Method not allowed', 'status' => 'METHOD_NOT_ALLOWED']]);

            return null;
        }

        $courseId = isset($classMatch[1]) ? rawurldecode($classMatch[1]) : null;
        $collection = $classMatch[2] ?? null;
        $courses = $state['classroom']['courses'] ?? [];
        if (! is_array($courses)) {
            $courses = [];
        }

        if ($courseId === null) {
            $wanted = array_filter(explode(',', (string) ($_GET['courseStates'] ?? 'ACTIVE')));
            $listed = array_values(array_filter($courses, function ($course) use ($wanted) {
                $stateName = (string) ($course['courseState'] ?? 'ACTIVE');

                return $wanted === [] || in_array($stateName, $wanted, true);
            }));
            $json(200, ['courses' => $listed]);

            return null;
        }

        $known = false;
        foreach ($courses as $course) {
            if ((string) ($course['id'] ?? '') === $courseId) {
                $known = true;
                break;
            }
        }
        if (! $known) {
            $json(404, ['error' => ['code' => 404, 'message' => 'Course not found', 'status' => 'NOT_FOUND']]);

            return null;
        }

        $key = $collection === 'courseWork' ? 'courseWork' : 'students';
        $rows = $state['classroom'][$key][$courseId] ?? [];
        $json(200, [$key => is_array($rows) ? array_values($rows) : []]);

        return null;
    });

    return;
}

if (preg_match('#^/calendar/v3/calendars(?:/([^/]+)(?:/events(?:/([^/]+))?)?)?$#', $path, $calMatch)) {
    $mutate(function (array &$state) use ($record, $bearer, $method, $path, $parsedBody, $json, $calMatch) {
        $state = $record($state);
        if (($state['modes']['calendar'] ?? null) === '500') {
            $json(500, ['error' => ['code' => 500, 'message' => 'backend error', 'status' => 'INTERNAL']]);

            return null;
        }
        if ($bearer === '' || ! isset($state['tokens'][$bearer]) || ! empty($state['account']['revoked'])) {
            $json(401, ['error' => ['code' => 401, 'message' => 'Invalid Credentials', 'status' => 'UNAUTHENTICATED']]);

            return null;
        }
        if (! isset($state['calendar']['calendars']) || ! is_array($state['calendar']['calendars'])) {
            $state['calendar'] = ['calendars' => [], 'next' => 1];
        }

        $calendarId = isset($calMatch[1]) ? rawurldecode($calMatch[1]) : null;
        $eventId = isset($calMatch[2]) ? rawurldecode($calMatch[2]) : null;
        $onEvents = str_contains($path, '/events');

        if ($calendarId === null && $method === 'POST') {
            $id = 'cal-'.$state['calendar']['next'];
            $state['calendar']['next']++;
            $state['calendar']['calendars'][$id] = [
                'id' => $id,
                'summary' => (string) ($parsedBody['summary'] ?? ''),
                'timeZone' => (string) ($parsedBody['timeZone'] ?? ''),
                'events' => [],
            ];
            $json(200, [
                'id' => $id,
                'summary' => $state['calendar']['calendars'][$id]['summary'],
                'timeZone' => $state['calendar']['calendars'][$id]['timeZone'],
            ]);

            return null;
        }

        if ($calendarId === null || ! isset($state['calendar']['calendars'][$calendarId])) {
            $json(404, ['error' => ['code' => 404, 'message' => 'Calendar not found', 'status' => 'NOT_FOUND']]);

            return null;
        }

        if (! $onEvents && $method === 'GET') {
            $calendar = $state['calendar']['calendars'][$calendarId];
            $json(200, [
                'id' => $calendar['id'],
                'summary' => $calendar['summary'],
                'timeZone' => $calendar['timeZone'],
            ]);

            return null;
        }

        if ($onEvents && $eventId === null && $method === 'GET') {
            $json(200, ['items' => array_values($state['calendar']['calendars'][$calendarId]['events'])]);

            return null;
        }

        if ($onEvents && $eventId === null && $method === 'POST') {
            $id = (string) ($parsedBody['id'] ?? '');
            if ($id === '') {
                $json(400, ['error' => ['code' => 400, 'message' => 'Event id is required', 'status' => 'INVALID_ARGUMENT']]);

                return null;
            }
            $stored = $parsedBody;
            $stored['id'] = $id;
            $state['calendar']['calendars'][$calendarId]['events'][$id] = $stored;
            $json(200, $stored);

            return null;
        }

        if ($onEvents && $eventId !== null && $method === 'GET') {
            if (! isset($state['calendar']['calendars'][$calendarId]['events'][$eventId])) {
                $json(404, ['error' => ['code' => 404, 'message' => 'Event not found', 'status' => 'NOT_FOUND']]);

                return null;
            }
            $json(200, $state['calendar']['calendars'][$calendarId]['events'][$eventId]);

            return null;
        }

        if ($onEvents && $eventId !== null && $method === 'PUT') {
            if (! isset($state['calendar']['calendars'][$calendarId]['events'][$eventId])) {
                $json(404, ['error' => ['code' => 404, 'message' => 'Event not found', 'status' => 'NOT_FOUND']]);

                return null;
            }
            $stored = $parsedBody;
            $stored['id'] = $eventId;
            $state['calendar']['calendars'][$calendarId]['events'][$eventId] = $stored;
            $json(200, $stored);

            return null;
        }

        if ($onEvents && $eventId !== null && $method === 'DELETE') {
            unset($state['calendar']['calendars'][$calendarId]['events'][$eventId]);
            $json(200, ['deleted' => true]);

            return null;
        }

        $json(405, ['error' => ['code' => 405, 'message' => 'Method not allowed', 'status' => 'METHOD_NOT_ALLOWED']]);

        return null;
    });

    return;
}

$json(404, ['error' => ['code' => 404, 'message' => 'No fake Google handler for '.$path, 'status' => 'NOT_FOUND']]);
