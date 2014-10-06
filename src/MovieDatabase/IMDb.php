<?php

namespace Mihaeu\MovieManager\MovieDatabase;

use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class IMDb
 *
 * Crawl IMDb for the IMDb rating (not available on TMDb).
 *
 * @author Michael Haeuslmann (haeuslmann@gmail.com)
 */
class IMDb
{
    const IMDB_BASE_URL = 'http://www.imdb.com/title/';

    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Find the IMDb user rating for a movie.
     *
     * @param  string $imdbId
     *
     * @return float|bool
     */
    public function getRating($imdbId)
    {
        try {
            /** @var ResponseInterface $response */
            $response = $this->client->get(self::IMDB_BASE_URL.$imdbId);
            $content = $response->getBody();
        } catch (\Exception $e) {
            return false;
        }

        $crawler = new Crawler();
        $crawler->addContent($content);
        $ratingCrawler = $crawler->filter('.star-box-giga-star');
        $rating = $ratingCrawler->text();
        return empty($rating) ? false : floatval($rating);
    }
}
