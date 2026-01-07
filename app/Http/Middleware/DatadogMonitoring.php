<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DatadogMonitoring
{
    /**
     * Handle an incoming request with Datadog monitoring
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Start timing
        $startTime = microtime(true);
        
        // Add custom tags
        if (function_exists('dd_trace_push_span_id')) {
            $span = \DDTrace\GlobalTracer::get()->getActiveSpan();
            if ($span) {
                $span->setTag('http.method', $request->method());
                $span->setTag('http.url', $request->fullUrl());
                $span->setTag('user.authenticated', auth()->check());
                
                if (auth()->check()) {
                    $span->setTag('user.id', auth()->id());
                }
            }
        }
        
        // Process request
        $response = $next($request);
        
        // Calculate response time
        $responseTime = (microtime(true) - $startTime) * 1000;
        
        // Send custom metrics
        $this->sendCustomMetrics($request, $response, $responseTime);
        
        return $response;
    }
    
    /**
     * Send custom metrics to Datadog
     */
    private function sendCustomMetrics(Request $request, Response $response, float $responseTime): void
    {
        if (!function_exists('dd_trace_send_metrics_to_agent')) {
            return;
        }
        
        $tags = [
            'env:' . config('app.env'),
            'service:pineus-tilu',
            'route:' . ($request->route()?->getName() ?? 'unknown'),
            'method:' . $request->method(),
            'status:' . $response->getStatusCode(),
        ];
        
        // Response time metric
        statsd_histogram('pineus_tilu.request.duration', $responseTime, $tags);
        
        // Request count
        statsd_increment('pineus_tilu.request.count', 1, $tags);
        
        // Status code metrics
        if ($response->getStatusCode() >= 400) {
            statsd_increment('pineus_tilu.request.errors', 1, $tags);
        }
        
        // Track specific business metrics
        if ($request->route()?->getName() === 'booking.store') {
            statsd_increment('pineus_tilu.booking.created', 1, $tags);
        }
        
        if ($request->route()?->getName() === 'booking.verifyPaymentOtp') {
            statsd_increment('pineus_tilu.payment.completed', 1, $tags);
        }
    }
}