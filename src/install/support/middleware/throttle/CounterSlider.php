<?php

declare(strict_types=1);

namespace support\http\middleware\throttle;

/**
 * 计数器滑动窗口算法
 *
 * @package topthink/think-throttle
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class CounterSlider extends ThrottleAbstract
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
        $history = $cache->get($key, []);
        $now = (int)$micronow;
        // 移除过期的请求的记录
        $history = array_values(array_filter($history, function ($val) use ($now, $duration) {
            return $val >= $now - $duration;
        }));

        $this->cur_requests = count($history);
        if ($this->cur_requests < $max_requests) {
            // 允许访问
            $history[] = $now;
            $cache->set($key, $history, $duration);
            return true;
        }

        if ($history) {
            $wait_seconds = $duration - ($now - $history[0]) + 1;
            $this->wait_seconds = max($wait_seconds, 0);
        }

        return false;
    }
}
