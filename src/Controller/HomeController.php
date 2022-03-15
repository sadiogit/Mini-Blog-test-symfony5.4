<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
   /* #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', []);
    }
*/
    #[Route('/', name: 'post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository): Response
    {
        
       
        $posts = $postRepository->findBy( array(),array('date_created' => 'DESC') );
        

        return $this->render('home/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    
    #[Route('/article/{id<[0-9]+>}-{slug}', name: 'post_show', methods: ['GET'], requirements: ['slug' =>'[a-z0-9\-]*'])] 
    public function show(Post $post, string $slug): Response
    {
        if ($post->getSlug() !== $slug) {
            return $this->redirectToRoute('post_show', [
                'id' => $post->getId(),
                'slug' => $post->getSlug(),
            ], 301);
        }

        return $this->render('home/show.html.twig', [
            'post' => $post,
        ]);
    }

}
