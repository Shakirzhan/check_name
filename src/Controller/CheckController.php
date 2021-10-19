<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Image;

class CheckController extends AbstractController
{
    private $slugger;
   
    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    private function upload(UploadedFile $file)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $safeFilename = $this->slugger->slug($originalFilename);

        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        $path = $_SERVER['DOCUMENT_ROOT'] . '/img/';

        if(!file_exists($path)) {
            mkdir($path, 0700);
        }

        try {
            $file->move($path, $fileName);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }

        return $path . $fileName;
    }

    public function check(Request $request)
    {
        $r = new Response();

        $name = $request->request->get('name');

        $file = $request->files->get('photo');

        $file = $this->upload($file);

        $fileName = $_FILES['photo']['name'];
        
        $formFields = [
            'name' => $name,
            'photo' => DataPart::fromPath($file),
        ];

        $formData = new FormDataPart($formFields);

    
        $httpClient = HttpClient::create();

        $repository = $this->getDoctrine()->getRepository(Image::class);

        $imgFind = $repository->findBy([
            'name' => $fileName
        ]);

        if(count($imgFind)) {
            return $r->setContent(json_encode([
                'status' => 'ready'
            ]));
        }

        $response = $httpClient
        ->request('POST', 'http://merlinface.com:12345/api/', [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ])
        ->getContent(false);

        $response = json_decode($response);

        $entityManager = $this->getDoctrine()->getManager();

        $image = new Image();

        $image->setName($fileName);

        $entityManager->persist($image);

        $entityManager->flush();

        return $r->setContent(json_encode($response));
    }

    public function getCheck(Request $request, $hash) 
    {
        $formFields = [
            'retry_id' => $hash
        ];

        $formData = new FormDataPart($formFields);

    
        $httpClient = HttpClient::create();
   
        $response = $httpClient
        ->request('POST', 'http://merlinface.com:12345/api/', [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ])
        ->getContent(false);

        $response = json_decode($response);

        $r = new Response();

        return $r->setContent(json_encode($response));
    }
}
