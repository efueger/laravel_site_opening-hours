<?php

namespace App\Repositories;

use App\Models\Service;

class ServicesRepository extends EloquentRepository
{
    public function __construct(Service $service)
    {
        parent::__construct($service);
    }

    /**
     * Return all services with their channels and openinghours (without the attached calendars)
     *
     * @return array
     */
    public function get()
    {
        $services = $this->model->get();

        $results = [];

        foreach ($services as $service) {
            $result = $service->toArray();
            $result['channels'] = [];

            foreach ($service->channels as $channel) {
                $tmpChannel = $channel->toArray();
                $tmpChannel['openinghours'] = [];

                foreach ($channel->openinghours as $openinghours) {
                    $tmpChannel['openinghours'][] = $openinghours->toArray();
                }

                $result['channels'][] = $tmpChannel;
            }

            $results[] = $result;
        }

        return $results;
    }
}