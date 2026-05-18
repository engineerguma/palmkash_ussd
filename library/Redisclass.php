<?php

class Redisclass
{
    private Redis $redis;
    private bool $connected = false;

    public function __construct()
    {
        $this->redis = new Redis();
    }

    public function connect(): void
    {
        if ($this->connected) {
            return;
        }

        try {
            $this->redis->pconnect(REDIS_HOST, REDIS_PORT, 2.5);

            if (defined('REDIS_PASSWORD') && REDIS_PASSWORD !== '') {
                $this->redis->auth(REDIS_PASSWORD);
            }

            $this->connected = true;
        } catch (RedisException $e) {
            error_log('Redis connection failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function DisConnect(): bool
    {
        if (!$this->connected) {
            return true;
        }

        $this->connected = false;
        return $this->redis->close();
    }

    public function DeleteforMultiple(string $key): int
    {
        $this->connect();

        return $this->redis->del($key);
    }

    public function DeleteKey(string $key): int
    {
        $this->connect();

        return $this->redis->del($key);
    }

    public function KeyExists(string $key): bool
    {
        $this->connect();

        return (bool) $this->redis->exists($key);
    }

    public function StoreKeyData(string $key, string $value, int $expiry = SESSION_ID_EXP): bool
    {
        $this->connect();

        $response = $this->redis->set($key, $value);

        if ($response && $expiry > 0) {
            $this->redis->expire($key, $expiry);
        }

        return (bool) $response;
    }

    public function GetKeyRecord(string $key): string|bool
    {
        $this->connect();

        return $this->redis->get($key);
    }

    public function StoreNameWitValue(string $key, string $name, mixed $value, int $expiry = SESSION_ID_EXP): int
    {
        $this->connect();

        $response = $this->redis->hSet($key, $name, $value);

        if ($expiry > 0) {
            $this->redis->expire($key, $expiry);
        }

        return $response;
    }

    public function GetRecordByValue(string $key, string $value): string|bool
    {
        $this->connect();

        return $this->redis->hGet($key, $value);
    }

    public function GetKeyRecords(string $key): array
    {
        $this->connect();

        $response = $this->redis->hGetAll($key);

        return is_array($response) ? $response : [];
    }

    public function StoreArrayRecords(string $key, array $array = [], int $expiry = SESSION_ID_EXP): bool
    {
        $this->connect();

        if (empty($array)) {
            return false;
        }

        $response = $this->redis->hMSet($key, $array);

        if ($response && $expiry > 0) {
            $this->redis->expire($key, $expiry);
        }

        return (bool) $response;
    }

    public function StoreCommonInputRecords(string $key, array $array = [], int $expiry = SESSION_ID_EXP): bool
    {
        $this->connect();

        if (empty($array)) {
            return false;
        }

        foreach ($array as $field => $value) {
            $this->redis->hSet($key, (string) $field, $value);
        }

        if ($expiry > 0) {
            $this->redis->expire($key, $expiry);
        }

        return true;
    }

    public function ExpireRecords(string $key, int $seconds = 200): bool
    {
        $this->connect();

        if ($seconds <= 0) {
            return false;
        }

        return (bool) $this->redis->expire($key, $seconds);
    }

    public function GetMatchingKeys(string $key_prefix): array
    {
        $this->connect();

        $iterator = null;
        $keys = [];
        $pattern = '*' . $key_prefix . '*';

        do {
            $result = $this->redis->scan($iterator, $pattern, 100);

            if ($result !== false) {
                $keys = array_merge($keys, $result);
            }
        } while ($iterator > 0);

        return $keys;
    }

    public function Close(): bool
    {
        return $this->DisConnect();
    }
}