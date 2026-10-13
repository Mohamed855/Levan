<?php

namespace App\Services\shared\Integration\WithOutTenant;

class EtmamTicketServiceWithOutTenant
{
    protected ?EtmamConnectorWithOutTenant $connector = null;

    protected function getConnector(): EtmamConnectorWithOutTenant
    {
        if ($this->connector === null) {
            $this->connector = new EtmamConnectorWithOutTenant;
        }

        return $this->connector;
    }

    public function isConfigured(): bool
    {
        return $this->getConnector()->isConfigured();
    }

    // الفترة التجريبية
    public function demoRequest(array $data)
    {
        return $this->getConnector()->post('demo-request', $data);
    }

    //  طلب استفسار
    public function contactRequest(array $data)
    {
        return $this->getConnector()->post('contact-request', $data);
    }
}
