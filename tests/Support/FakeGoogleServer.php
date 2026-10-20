<?php

namespace Tests\Support;

use RuntimeException;

/**
 * Boots tests/fixtures/google/fake-google-router.php on 127.0.0.1.
 * The same shape as the WhatsApp simulator: a real HTTP process, not Http::fake().
 */
class FakeGoogleServer
{
    public const CLIENT_ID = 'test-google-client';

    public const CLIENT_SECRET = 'test-google-secret';

    private $process = null;

    private string $stateFile;

    private int $port;

    public function start(): void
    {
        $this->stateFile = tempnam(sys_get_temp_dir(), 'gfake');
        if ($this->stateFile === false) {
            throw new RuntimeException('Could not create the fake Google state file.');
        }
        file_put_contents($this->stateFile, json_encode($this->freshState()));

        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($socket === false) {
            throw new RuntimeException('Could not reserve a port: '.$errstr);
        }
        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        $this->port = (int) substr((string) $name, strrpos((string) $name, ':') + 1);

        $router = dirname(__DIR__).'/fixtures/google/fake-google-router.php';
        $command = [PHP_BINARY, '-S', '127.0.0.1:'.$this->port, $router];
        $log = sys_get_temp_dir().'/gfake-'.$this->port.'.log';
        $this->process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['file', $log, 'a'],
            2 => ['file', $log, 'a'],
        ], $pipes, null, [
            'GOOGLE_FAKE_STATE' => $this->stateFile,
            'GOOGLE_FAKE_CLIENT_ID' => self::CLIENT_ID,
            'GOOGLE_FAKE_CLIENT_SECRET' => self::CLIENT_SECRET,
        ]);

        if (! is_resource($this->process)) {
            throw new RuntimeException('Could not start the fake Google server.');
        }

        $deadline = microtime(true) + 5;
        $ready = false;
        while (microtime(true) < $deadline) {
            $fp = @fsockopen('127.0.0.1', $this->port, $errno, $errstr, 0.2);
            if (is_resource($fp)) {
                fclose($fp);
                $ready = true;
                break;
            }
            usleep(20000);
        }

        if (! $ready) {
            $err = is_file($log) ? (string) file_get_contents($log) : '';
            $this->stop();
            throw new RuntimeException('Fake Google server did not listen: '.$err);
        }
    }

    public function stop(): void
    {
        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
            $this->process = null;
        }
        if (isset($this->stateFile) && is_file($this->stateFile)) {
            @unlink($this->stateFile);
        }
    }

    public function baseUrl(): string
    {
        return 'http://127.0.0.1:'.$this->port;
    }

    public function url(string $path): string
    {
        return $this->baseUrl().'/'.ltrim($path, '/');
    }

    /**
     * @return array<string, mixed>
     */
    public function state(): array
    {
        return $this->withState(fn (array $state) => $state);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function calls(): array
    {
        return $this->state()['calls'] ?? [];
    }

    /**
     * @param  callable(array<string, mixed>): void  $fn
     */
    public function update(callable $fn): void
    {
        $this->withState(function (array &$state) use ($fn) {
            $fn($state);

            return $state;
        });
    }

    public function reset(): void
    {
        $this->withState(function (array &$state) {
            $state = $this->freshState();

            return $state;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function freshState(): array
    {
        return [
            'account' => [
                'sub' => '1001',
                'email' => 'school@example.test',
                'scopes' => [],
                'revoked' => false,
            ],
            'codes' => [],
            'tokens' => [],
            'refresh' => null,
            'calls' => [],
            'modes' => [
                'refresh' => null,
                'userinfo' => null,
                'token' => null,
            ],
            'sheets' => [],
            'drive' => ['files' => [], 'next' => 1],
            'classroom' => ['courses' => []],
        ];
    }

    /**
     * @param  callable(array<string, mixed>): mixed  $fn
     */
    private function withState(callable $fn): mixed
    {
        $fh = fopen($this->stateFile, 'c+');
        if ($fh === false) {
            throw new RuntimeException('Could not open the fake Google state file.');
        }
        flock($fh, LOCK_EX);
        $state = json_decode(stream_get_contents($fh) ?: '{}', true);
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
    }
}
