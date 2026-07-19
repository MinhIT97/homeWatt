<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustProxies
{
    /**
     * The trusted proxies for this application.
     *
     * Configure via TRUSTED_PROXIES env (comma-separated IPs/CIDRs).
     * In production, set this to your load balancer/CDN IPs.
     */
    protected $proxies;

    /**
     * The current proxy header mappings.
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    public function handle(Request $request, Closure $next): Response
    {
        $proxies = config('app.trusted_proxies');

        if (is_string($proxies) && $proxies !== '') {
            $this->proxies = array_map('trim', explode(',', $proxies));
        } elseif (app()->environment('local')) {
            $this->proxies = '*'; // Trust all in local dev only
        } else {
            $this->proxies = []; // Trust no proxies by default
        }

        Request::setTrustedProxies($this->proxies, $this->headers);

        return $next($request);
    }
}
