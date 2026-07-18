<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final class FileUploader
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        #[Autowire('%justificatifs_directory%')]
        private readonly string $targetDirectory,
    ) {
    }

    /**
     * Déplace le fichier dans le répertoire cible sous un nom unique et sûr.
     *
     * @return string le nom du fichier déposé
     *
     * @throws FileException si le déplacement échoue
     */
    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $file->move($this->targetDirectory, $newFilename);

        return $newFilename;
    }
}
