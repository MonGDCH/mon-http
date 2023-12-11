<?php

declare(strict_types=1);

namespace support\http\middleware\throttle;

/**
 * 令牌桶算法
 * 
 * @package topthink/think-throttle
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class TokenBucket extends ThrottleAbstract
{
    /**
     * 是否允许请求
     *
     * @param string $key 缓存键
     * @param float $micronow 当前时间戳,可含毫秒
     * @param integer $max_requests 允许最大请求数
     * @param integer $duration 限流时长
     * @param \Psr\SimpleCache\CacheInterface $cache 缓存对象
     * @return boolean
     */
    public function allowRequest(string $key, float $micronow, int $max_requests, int $duration, $cache): bool
    {
        if ($max_requests <= 0 || $duration <= 0) {
            return false;
        }
        // 辅助缓存
        $assist_key = $key . 'store_num';
        // 平均一秒生成 n 个 token
        $rate = (float)$max_requests / $duration;

        $last_time = $cache->get($key, null);
        $store_num = $cache->get($assist_key, null);

        // 首次访问
        if ($last_time === null || $store_num === null) {
            $cache->set($key, $micronow, $duration);
            $cache->set($assist_key, $max_requests - 1, $duration);
            return true;
        }

        // 推算生成的 token 数
        $create_num = floor(($micronow - $last_time) * $rate);
        // 当前剩余 tokens 数量  
        $token_left = (int)min($max_requests, $store_num + $create_num);

        if ($token_left < 1) {
            $tmp = (int)ceil($duration / $max_requests);
            $this->wait_seconds = $tmp - intval(($micronow - $last_time)) % $tmp;
            return false;
        }
        $this->cur_requests = $max_requests - $token_left;
        $cache->set($key, $micronow, $duration);
        $cache->set($assist_key, $token_left - 1, $duration);
        return true;
    }
}
