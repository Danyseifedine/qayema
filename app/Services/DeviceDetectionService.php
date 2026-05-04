<?php

namespace App\Services;

class DeviceDetectionService
{
    public function getDeviceType(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'unknown';
        }

        $ua = strtolower($userAgent);

        if (preg_match('/tablet|ipad|playbook|silk/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    public function getBrowser(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown';
        }

        $ua = strtolower($userAgent);

        if (strpos($ua, 'edg') !== false) {
            return 'Edge';
        }
        if (strpos($ua, 'opr') !== false || strpos($ua, 'opera') !== false) {
            return 'Opera';
        }
        if (strpos($ua, 'chrome') !== false) {
            return 'Chrome';
        }
        if (strpos($ua, 'firefox') !== false) {
            return 'Firefox';
        }
        if (strpos($ua, 'safari') !== false) {
            return 'Safari';
        }

        return 'Unknown';
    }

    public function getOS(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown';
        }

        $ua = strtolower($userAgent);

        if (strpos($ua, 'android') !== false) {
            return 'Android';
        }
        if (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false) {
            return 'iOS';
        }
        if (strpos($ua, 'windows') !== false) {
            return 'Windows';
        }
        if (strpos($ua, 'mac') !== false) {
            return 'macOS';
        }
        if (strpos($ua, 'linux') !== false) {
            return 'Linux';
        }

        return 'Unknown';
    }
}
