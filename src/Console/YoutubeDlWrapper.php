<?php declare(strict_types = 1);

namespace Mihaeu\MovieManager\Console;

class YoutubeDlWrapper
{
    public function download(string $youtubeUrl, string $movieFilenameWithoutExt) : bool
    {
        $this->ensureYoutubeDlIsInstalled();

        $cmd = 'youtube-dl '
            . "--format 18 '$youtubeUrl' "
            . "--output '$movieFilenameWithoutExt- Trailer.%(ext)s'";
        exec($cmd);

        return $this->trailerExistsInDestination($movieFilenameWithoutExt);
    }

    private function ensureYoutubeDlIsInstalled()
    {
        if (exec('youtube-dl --version') < 1) {
            throw new \InvalidArgumentException('YoutubeDl not installed, try running with the --no-trailer flag');
        }
    }

    private function trailerExistsInDestination($movieFilenameWithoutExt)
    {
        foreach (scandir(dirname($movieFilenameWithoutExt)) as $file) {
            if (strpos($file, basename($movieFilenameWithoutExt) . ' - Trailer.') !== false) {
                return true;
            }
        }
        return false;
    }
}
