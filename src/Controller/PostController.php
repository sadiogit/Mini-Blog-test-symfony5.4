<?php

namespace App\Controller;

use App\Entity\Images;
use App\Entity\Post;
use App\Form\PostType;
use App\Repository\ImagesRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\Query\Expr\Func;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class PostController extends AbstractController
{

    #[Route('/', name: 'app_post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository): Response
    {
        
        $posts = $postRepository->findBy( array(),array('date_created' => 'DESC') );

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/article/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PostRepository $postRepository): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        
        if ($form->isSubmitted() && $form->isValid()) {

          
            $images = $form->get('images')->getData();

            foreach($images as $image){ 
                $fichier = md5(uniqid()). '.' . $image->guessExtension();
               
                $image->move(
                    $this->getParameter('images_directory'),
                    $fichier
                );
                $img = new Images();
                $img->setCover($fichier);
                $post->addImage($img);
              
            }
 
            $postRepository->add($post);
            return $this->redirectToRoute('app_post_index');
        
           // return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->renderForm('post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/article/{id<[0-9]+>}-{slug}', name: 'app_post_show', methods: ['GET'], requirements: ['slug' =>'[a-z0-9\-]*'])] 
    public function show(Post $post, string $slug): Response
    {
        if ($post->getSlug() !== $slug) {
            return $this->redirectToRoute('app_post_show', [
                'id' => $post->getId(),
                'slug' => $post->getSlug(),
            ], 301);
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/article/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, PostRepository $postRepository): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

           
            $images = $form->get('images')->getData();
           
            foreach($images as $image){ 
                $fichier = md5(uniqid()). '.' . $image->guessExtension();
              
                $image->move(
                    
                    $this->getParameter('images_directory'),
                    $fichier
                );
               
                $img = new Images();
                $img->setCover($fichier);
                $post->addImage($img);
              
            }

            $postRepository->add($post);
            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/article/{id}', name: 'app_post_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, PostRepository $postRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $postRepository->remove($post);
        }

        return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/article/supprimer/image/{id}', name: 'app_post_delete_image', methods: ['DELETE'])]
    public function deleteImage(Request $request, Images $image): Response
    {
        $data = json_decode($request->getContent(), true);
        // je verifie si le token est valide
        if($this->isCsrfTokenValid('delete'.$image->getId(), $data['_token'])){
            $cover = $image->getCover();
            // suppresion de fichier
            unlink($this->getParameter('images_directory').'/'.$cover);
            // suppresion de l'entrée de la base
            $em = $this->getDoctrine()->getManager();
            $em->remove($image);
            $em->flush();

            return new JsonResponse(['success' => 1]);

        }else {
            return new JsonResponse(['error' => 'Token invalide'], 400);
        }

       // return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
    }
}
