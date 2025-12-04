<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\Exporter\OtlpHttp;
use OpenTelemetry\Context\Context;
use OpenTelemetry\API\Logs\LoggerProviderInterface;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\Exporter\OtlpHttp as LogExporter;
use OpenTelemetry\SDK\Common\Time\ClockFactory;

class OpenTelemetryServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Tracer
        $this->app->singleton(TracerProviderInterface::class, function () {
            $endpoint = env('OTEL_EXPORTER_OTLP_TRACES_ENDPOINT', 'http://otel-collector:4318/v1/traces');
            $exporter = new OtlpHttp\Exporter($endpoint);
            $processor = new BatchSpanProcessor($exporter);
            return new TracerProvider($processor);
        });

        $this->app->singleton('otel.tracer', function ($app) {
            return $app->make(TracerProviderInterface::class)->getTracer('laravel-app');
        });

        // Logger
        $this->app->singleton(LoggerProviderInterface::class, function () {
            $endpoint = env('OTEL_EXPORTER_OTLP_LOGS_ENDPOINT', 'http://otel-collector:4318/v1/logs');
            $exporter = new LogExporter($endpoint);
            return new LoggerProvider(
                $exporter,
                ClockFactory::getDefault()
            );
        });

        $this->app->singleton('otel.logger', function ($app) {
            return $app->make(LoggerProviderInterface::class)->getLogger('laravel-app');
        });
    }

    public function boot()
    {
        if (app()->runningInConsole()) return;

        $tracer = app('otel.tracer');
        $request = request();

        $span = $tracer->spanBuilder('HTTP ' . $request->method() . ' ' . $request->path())
            ->setSpanKind(\OpenTelemetry\API\Trace\SpanKind::KIND_SERVER)
            ->startSpan();

        $context = $span->storeInContext(Context::getCurrent());
        Context::storage()->attach($context);

        // Log request
        $logger = app('otel.logger');
        $logger->log(\Psr\Log\LogLevel::INFO, 'Incoming request', [
            'method' => $request->method(),
            'uri' => $request->fullUrl(),
            'traceID' => $span->getContext()->getTraceId(),
            'spanID' => $span->getContext()->getSpanId(),
        ]);

        register_shutdown_function(function () use ($span) {
            $span->end();
        });
    }
}