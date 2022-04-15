<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Un service qui nous permet d'uploader un fichier de type image
 */
class FileUploader{


    private $slugger;
    private $targetDirectory;

    public function __construct(SluggerInterface $slugger, string $destination)
    {
        $this->slugger = $slugger;
        $this->targetDirectory=$destination;
    }
/**
 * Methode d'upload d'une image
 * Elle prend en parametre une variable
 */
    public function upload($imgFile){
        $originalFilename = pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME);
       
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