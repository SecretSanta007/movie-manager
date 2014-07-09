<?php

namespace Mihaeu\MovieManager;

/**
 * Class HtmlBuilder
 *
 * @package Mihaeu\MovieManager
 *
 * @author Michael Haeuslmann (haeuslmann@gmail.com)
 */
class HtmlBuilder
{
    /**
     * @var \Twig_Environment
     */
    private $templating;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setUpTemplating();
    }

    /**
     * Builds the HTML.
     *
     * @param  string $pathToMovies
     * @param  int    $limit
     * @return string
     */
    public function build($pathToMovies, $limit = -1)
    {
        $movies         = [];
        $moviesJson     = [];
        $movieYears     = [];
        $movieGenres    = [];
        $movieLanguages = [];
        $movieCountries = [];

        $movieFolders = array_diff(scandir($pathToMovies), ['.', '..']);
        foreach ($movieFolders as $movieFolder) {
            if (--$limit === 0) {
                break;
            }

            $linkFile = "$pathToMovies/$movieFolder/$movieFolder - IMDb.url";
            $movieInfo = $this->readMovieInfoFromLinkFile($linkFile);
            if (false === $movieInfo) {
                continue;
            }

            if (isset($movieInfo['info'])) {
                $posterFile = str_replace('- IMDb.url', '- Poster.jpg', $linkFile);
                $cast = [];
                if (isset($movieInfo['cast'])) {
                    foreach ($movieInfo['cast'] as $id => $name) {
                        $character = '';
                        if (isset($movieInfo['character'][$id])) {
                            $character = $movieInfo['character'][$id];
                        }
                        $cast[] = [
                            'id' => $id,
                            'name' => $name,
                            'character' => $character
                        ];
                    }
                }

                $movie = [
                    'id'         => $movieInfo['info']['imdb_id'],
                    'title'      => $movieInfo['info']['title'],
                    'year'       => preg_replace('/(\d{4}).*/', '$1', $movieInfo['info']['release_date']),
                    'directors'  => isset($movieInfo['directors']) ? $movieInfo['directors'] : '',
                    'cast'       => isset($cast) ? $cast : '',
                    'rating'     => isset($movieInfo['info']['imdb_rating']) ? $movieInfo['info']['imdb_rating'] : $movieInfo['info']['vote_average'],
                    'length'     => $movieInfo['info']['runtime'],
                    'genre'      => isset($movieInfo['genres']) ? array_values($movieInfo['genres']) : [],
                    'languages'  => isset($movieInfo['spoken_languages']) ? array_values(
                                        $movieInfo['spoken_languages']
                                    ) : [],
                    'countries'  => isset($movieInfo['production_countries']) ? array_values(
                                        $movieInfo['production_countries']
                                    ) : [],
                    'plot'       => $movieInfo['info']['overview'],
                    'tagline'    => $movieInfo['info']['tagline']
                ];

                // the poster should not be part of the json file, so let's add that later
                $moviesJson[$movie['id']] = $movie;
                $movie['poster'] = $this->getScaledPosterAsBase64($posterFile, 400, 266);

                $movieYears[$movie['year']] = $movie['year'];
                foreach ($movie['genre'] as $genre) {
                    $movieGenres[$genre] = $genre;
                }
                if (isset($movieInfo['spoken_languages'])) {
                    foreach ($movieInfo['spoken_languages'] as $language) {
                        if (!empty($language)) {
                            $movieLanguages[$language] = $language;
                        }
                    }
                }
                foreach ($movie['countries'] as $country) {
                    $movieCountries[$country] = $country;
                }
                $movies[$movie['id']] = $movie;
            }
        }

        asort($movieYears);
        return $this->templating->render(
            'collection.html.twig',
            [
                'years' => $movieYears,
                'genres' => $movieGenres,
                'languages' => $movieLanguages,
                'countries' => $movieCountries,
                'movies' => $movies,
                'moviesJson' => str_replace("'", '&#39;', json_encode($moviesJson))
            ]
        );
    }

    /**
     * Scales a .jpeg image and returns the base64 encoded data.
     *
     * @param string $file
     * @param int    $newHeight
     * @param int    $newWidth
     *
     * @return string
     */
    public function getScaledPosterAsBase64($file, $newHeight = 400, $newWidth = 0)
    {
        if (!file_exists($file)) {
            return '';
        }

        list($width, $height) = getimagesize($file);
        if ($newWidth === 0) {
            $newWidth = $width / ($height / $newHeight);
        }

        $original = @imagecreatefromjpeg($file);
        if ($original === false) {
            return '';
        }
        $thumb = imagecreatetruecolor($newWidth, $newHeight);

        imagecopyresized($thumb, $original, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // buffer output
        ob_start();
        imagejpeg($thumb);
        $img = ob_get_clean();

        return base64_encode($img);
    }

    /**
     * Sets up Twig environment, extensions and functions.
     */
    public function setUpTemplating()
    {
        $loader = new \Twig_Loader_Filesystem(
            [
                __DIR__ . '/../../../templates/movie-collection',
                __DIR__ . '/../../../templates/movie-collection/assets/css',
                __DIR__ . '/../../../templates/movie-collection/assets/js'
            ]
        );
        $this->templating = new \Twig_Environment($loader, ['debug' => true]);
        $this->templating->addExtension(new \Twig_Extension_Debug());

        // add custom functions
        $this->templating->addFunction(
            new \Twig_SimpleFunction(
                'filedump', function ($file) {
                    $data = file_get_contents(__DIR__.'/../../../templates/movie-collection/'.$file);
                    echo $data;
                }
            )
        );
        $this->templating->addFunction(
            new \Twig_SimpleFunction(
                'base64filedump', function ($file) {
                    $data = file_get_contents(__DIR__.'/../../../templates/movie-collection/'.$file);
                    echo base64_encode($data);
                }
            )
        );
    }

    /**
     * Reads all the movie information from a windows style .url file.
     *
     * @param string $linkFile
     *
     * @return array|bool
     */
    public function readMovieInfoFromLinkFile($linkFile)
    {
        $iniContent = @file_get_contents($linkFile);
        if (false === $iniContent) {
            return false;
        }

        // problem: certain keys are not allowed in .ini files
        // find and fix them
        $replacements = [
            'no='       => 'no_=',
            'on='       => 'on_=',
            'yes='      => 'yes_=',
            'off='      => 'off_=',
            'true='     => 'true_=',
            'null='     => 'null_=',
            'none='     => 'none_=',
            'false='    => 'false_='
        ];
        $saveIniContent = str_replace(array_keys($replacements), array_values($replacements), $iniContent);

        return @parse_ini_string($saveIniContent, true);
    }
}
