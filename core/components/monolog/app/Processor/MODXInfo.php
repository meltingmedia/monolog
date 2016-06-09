<?php namespace Melting\MODX\Logger\Processor;

use modX;

/**
 * A basic processor for Monolog to consume additional MODX data in each log entry
 */
class MODXInfo
{
    /**
     * @var modX
     */
    protected $modx;
    protected $prefix = 'modx';

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    public function __invoke(array $record)
    {
        $record['extra']["{$this->prefix}_context"] = $this->modx->context->get('key');
        $record['extra']["{$this->prefix}_user"] = $this->modx->user->get('username');
        $record['extra']["{$this->prefix}_culture"] = $this->modx->cultureKey;
//        $record['extra']["{$this->prefix}_client_ip"] = $this->modx->request->getClientIp();
//        $record['extra']["{$this->prefix}_request_headers"] = $this->modx->request->getHeaders();
//        $record['extra']["{$this->prefix}_request_parameters"] = $this->modx->request->parameters;

        return $record;
    }
}
