<?php

namespace ZFort\Seoable\Services;

use ZFort\Seoable\Contracts\Seoable;
use ZFort\Seoable\Protocols\Meta;
use ZFort\Seoable\Protocols\OpenGraph;
use ZFort\Seoable\Protocols\Protocol;
use ZFort\Seoable\Protocols\TwitterCard;

class SeoModelService
{
    /**
     * @var Seoable
     */
    protected $model;

    protected $data;

    /**
     * SeoService constructor.
     * @param Seoable $model
     */
    public function __construct(Seoable $model)
    {
        $this->model = $model;

        $this->mergeData();
    }

    /**
     * Setup parsable and raw seo fields
     */
    public function parseData()
    {
        $this->fillSeoData(
            $this->data->parse,
            new Meta($this->model),
            new OpenGraph($this->model),
            new TwitterCard($this->model)
        );

        // Fill raw data
        $this->fillSeoData(
            $this->data->raw,
            new Meta($this->model, false),
            new OpenGraph($this->model, false),
            new TwitterCard($this->model, false)
        );
    }

    /**
     * @param $data
     * @param Meta $meta
     * @param OpenGraph $openGraph
     * @param TwitterCard $twitterCard
     */
    protected function fillSeoData($data, Meta $meta, OpenGraph $openGraph, TwitterCard $twitterCard)
    {
        $this->process($meta, array_except(
            (array)$data,
            ['open_graph', 'twitter_card']
        ));

        if (isset($data['open_graph'])) {
            $this->process($openGraph, $data['open_graph']);
        }

        if (isset($data['twitter_card'])) {
            $this->process($twitterCard, $data['twitter_card']);
        }
    }

    /**
     * Merge data from model configuration with table data
     */
    protected function mergeData()
    {
        $this->data = (object)[
            'parse' => (array)$this->model->seoable(),
            'raw' => (array)$this->model->getSeoData()
        ];

        // Process raw filed from model configuration
        foreach ($this->data->parse as $field => $item) {
            if (ends_with($field, '_raw')) {
                $field_name = strstr($field, '_raw', true);
                if (! isset($this->data->raw[$field_name])) {
                    $this->data->raw[$field_name] = $item;
                }
                unset($this->data->parse[$field]);
            }
        }

        foreach ($this->data->raw as $field => $value) {
            if (isset($this->data->parse[$field])) {
                unset($this->data->parse[$field]);
            }
        }
    }

    /**
     * Setup seo tags
     *
     * @param Protocol $protocol
     * @param array $config
     */
    protected function process(Protocol $protocol, array $config)
    {
        foreach ($config as $field => $value) {
            $protocol->{'set' . studly_case($field)}($value);
        }
    }
}