<?php
/**
 * OctoberCMS plugin: Adrenth.Redirect
 *
 * Copyright (c) Alwin Drenth 2017.
 *
 * Licensing information:
 * https://octobercms.com/help/license/regular
 * https://octobercms.com/help/license/extended
 * https://octobercms.com/help/license/faqs
 */

declare(strict_types=1);

namespace Adrenth\Redirect\Classes;

use Adrenth\Redirect\Models\Client;
use Adrenth\Redirect\Models\Redirect;
use Carbon\Carbon;
use October\Rain\Database\Collection;

/**
 * Class StatisticsHelper
 *
 * @package Adrenth\Redirect\Classes
 */
class StatisticsHelper
{
    /**
     * @return int
     */
    public function getTotalRedirectsServed(): int
    {
        return Client::count();
    }

    /**
     * @return Client|null
     */
    public function getLatestClient(): ?Client
    {
        return Client::orderBy('timestamp', 'desc')->limit(1)->first();
    }

    /**
     * @return int
     */
    public function getTotalThisMonth(): int
    {
        return Client::where('month', '=', date('m'))
            ->where('year', '=', date('Y'))
            ->count();
    }

    /**
     * @return int
     */
    public function getTotalLastMonth(): int
    {
        $lastMonth = Carbon::today();
        $lastMonth->subMonthNoOverflow();

        return Client::where('month', '=', $lastMonth->month)
            ->where('year', '=', $lastMonth->year)
            ->count();
    }

    /**
     * @return array
     */
    public function getActiveRedirects(): array
    {
        $groupedRedirects = [];

        /** @var Collection $redirects */
        $redirects = Redirect::enabled()
            ->get()
            ->filter(function (Redirect $redirect) {
                return $redirect->isActiveOnDate(Carbon::today());
            });

        /** @var Redirect $redirect */
        foreach ($redirects as $redirect) {
            $groupedRedirects[$redirect->getAttribute('status_code')][] = $redirect;
        }

        return $groupedRedirects;
    }

    /**
     * @return int
     */
    public function getTotalActiveRedirects(): int
    {
        return Redirect::enabled()
            ->get()
            ->filter(function (Redirect $redirect) {
                return $redirect->isActiveOnDate(Carbon::today());
            })
            ->count();
    }

    /**
     * @param bool $crawler
     * @return array
     */
    public function getRedirectHitsPerDay($crawler = false): array
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $result = Client::selectRaw('COUNT(id) AS hits')
            ->addSelect('day', 'month', 'year')
            ->groupBy('day', 'month', 'year')
            ->orderByRaw('year ASC, month ASC, day ASC');

        if ($crawler) {
            $result->whereNotNull('crawler');
        } else {
            $result->whereNull('crawler');
        }

        return $result->limit(365)
            ->get()
            ->toArray();
    }

    /**
     * @return array
     */
    public function getRedirectHitsPerMonth(): array
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        return (array) Client::selectRaw('COUNT(id) AS hits')
            ->addSelect('month', 'year')
            ->groupBy('month', 'year')
            ->orderByRaw('year DESC, month DESC')
            ->limit(12)
            ->get()
            ->toArray();
    }

    /**
     * @return array
     */
    public function getTopTenCrawlersThisMonth(): array
    {
        return (array) Client::selectRaw('COUNT(id) AS hits')
            ->addSelect('crawler')
            ->whereNotNull('crawler')
            ->where('month', '=', (int) date('n'))
            ->where('year', '=', (int) date('Y'))
            ->groupBy('crawler')
            ->orderByRaw('hits DESC')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * @param int $limit
     * @return array
     */
    public function getTopRedirectsThisMonth($limit = 10): array
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        return (array) Client::selectRaw('COUNT(redirect_id) AS hits')
            ->addSelect('redirect_id', 'r.from_url')
            ->join('adrenth_redirect_redirects AS r', 'r.id', '=', 'redirect_id')
            ->where('month', '=', (int) date('n'))
            ->where('year', '=', (int) date('Y'))
            ->groupBy('redirect_id', 'r.from_url')
            ->orderByRaw('hits DESC')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
