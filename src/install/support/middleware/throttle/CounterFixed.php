<?php

declare(strict_types=1);

namespace support\http\middleware\throttle;

/**
 * 计数器固定窗口算法
 * 
 * @package topthink/think-throttle
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class CounterFixed extends ThrottleAbstract
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
        $cur_requests = (int)$cache->get($key, 0);
        $now = (int)$micronow;
        // 距离下次重置还有n秒时间
        $wait_reset_seconds = $duration - $now % $duration;
        $this->wait_seconds = $wait_reset_seconds % $duration + 1;
        $this->cur_requests = $cur_requests;

        if ($cur_requests < $max_requests) {
            // 允许访问
            $cache->set($key, $this->cur_requests + 1, $wait_reset_seconds);
            return true;
        }
        return false;
    }
}
