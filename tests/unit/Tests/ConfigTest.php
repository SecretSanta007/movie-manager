<?php declare(strict_types = 1);

namespace Mihaeu\MovieManager\Tests;

use Mihaeu\MovieManager\Config;

class ConfigTest extends BaseTestCase
{
    /**
     * @expectedException \Exception
     */
    public function testBadConfigFile()
    {
        new Config('/doesnotexist/config.yml');
    }

    public function testTmdbApiKey()
    {
        $config = new Config();
        $this->assertNotEmpty($config->tmdbApiKey());
    }

    public function testAllowedMovieFormats()
    {
        $config = new Config();
        $this->assertNotEmpty($config->allowedMovieFormats());
    }
}
