<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader{
    
    private $slugger;
    private $targetDirectory;

    public function __construct(SluggerInterface $slugger, string $destination)
    {
        $this->slugger = $slugger;
        $this->targetDirectory=$destination;
    }

    public function upload($imgFile){
        $originalFilename = pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME);
        // slugger c'est pr effacer les espaces et caracteres speciaux
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $imgFile->guessExtension();

        try {
            $imgFile->move(
               $this->targetDirectory,
                $newFilename
            );
           return $newFilename;
        } catch (FileException $e) {
            return null;
        }
    }
    

}