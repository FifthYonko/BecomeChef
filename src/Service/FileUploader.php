<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Un service qui nous permet d'uploader un fichier de type image
 */
class FileUploader{

    // les attributs de la classe

    private $slugger;
    private $targetDirectory;

    public function __construct(SluggerInterface $slugger, string $destination)
    {
        // on defini l'attribut $slugger de la classe comme etant un objet de type SluggerInterface
        $this->slugger = $slugger;
        $this->targetDirectory=$destination;
    }
/**
 * Methode d'upload d'une image
 * Elle prend en parametre une variable
 */
    public function upload($imgFile){
        // on recupere le nom original du fichier 
        $originalFilename = pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME);
        // slugger c'est pr effacer les espaces et caracteres speciaux
        // on modifie le nom original de maniere safe
        $safeFilename = $this->slugger->slug($originalFilename);
        // on cree un nouveau nom grace au nom safe et a l'extension de l'image d'origine
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $imgFile->guessExtension();

        // on essaie de deplacer l'image uploade par l'utilisateur dans un fichier temp, 
        // dans le dossier final
        try {
            $imgFile->move(
               $this->targetDirectory,
                $newFilename
            );
            // si on reussi on renvoie le nvx nom du fichier
           return $newFilename;
        //    sinon on renvoie null
        } catch (FileException $e) {
            return null;
        }
    }
    

}