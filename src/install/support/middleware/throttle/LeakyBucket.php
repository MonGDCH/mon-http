<?php

declare(strict_types=1);

namespace support\http\middleware\throttle;

/**
 * 漏桶算法
 * 
 * @package topthink/think-throttle
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class LeakyBucket extends ThrottleAbstract
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
        if ($max_requests <= 0) {
            return false;
        }

        // 最近一次请求
        $last_time = (float)$cache->get($key, 0);
        // 平均 n 秒一个请求
        $rate = (float)$duration / $max_requests;
        if ($micronow - $last_time < $rate) {
            $this->cur_requests = 1;
            $this->wait_seconds = ceil($rate - ($micronow - $last_time));
            return false;
        }

        $cache->set($key, $micronow, $duration);
        return true;
    }
}
