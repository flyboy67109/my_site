<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Posts;
use App\Entity\Sections;
use App\Entity\Config;
use App\Service\GalleryService;
//use App\Entity\Images;

class RadioController extends AbstractController
{
    /**
     * @Route("/radio", name="radio")
     */
    public function index(Request $request)
    {
        // maintenance flag. set to true to display maintenance page
        $config = $this->getDoctrine()->getRepository(Config::class)->findOneBy(array());
        $maintenance = $config->getMaintenance();
        if ($maintenance) {
            return self::render('default/index.html.twig', ['route' => 'homepage']);
        }

        // gallery service
        $gallery = new GalleryService($this->getDoctrine()->getManager());

        // replace this example code with whatever you need
        return $this->render('radio/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
            'filter'    =>  'radio',
            'page'  =>  1,
            'route' =>  'radio',
            'last_post'  =>  $gallery->lastpostAction()
        ]);
    }

    /**
     * @param null $post
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/radio/post/{post}", name="radio/post")
     */
    public function postAction($post=null)
    {
        // maintenance flag. set to true to display maintenance page
        $config = $this->getDoctrine()->getRepository(Config::class)->findOneBy(array());
        $maintenance = $config->getMaintenance();
        if ($maintenance) {
            return self::render('default/index.html.twig', ['route' => 'homepage']);
        }

        $em = $this->getDoctrine()->getRepository(Posts::class );

        if (is_null($post)){
            $result = [];
        }elseif (is_numeric($post)) {
            //get the post by postId
            $result = $em->find($post);
            //see if slug is available and 301 redirect if found
            if (!empty($result->getSlug())){
                return $this->redirectToRoute("radio/post",["post"=>$result->getSlug()],301);
            }
        }else{
            //get the post by the slug
            $result = $em->findOneBy(array('slug'=>$post));
        }

        $sections = $this->getSections();

        if (is_array($result)){
            $log = [
                'path'  =>  "radio"
            ];
        }elseif(!is_null($result)) {
            $post_id = $result->getId();

            $nextPost = $this->getNextPost($post_id, $em);
            $prevPost = $this->getPreviousPost($post_id, $em);

            if (!is_null($result)) {

                $log = [
                    'id' => $result->getId(),
                    'title' => $result->getTitle(),
                    'log' => $result->getLog(),
                    'postDate' => $result->getPostDate(),
                    'section' => $sections[$result->getSectionId()],
                    'buildHours' => $result->getBuildHours(),
                    'nextPost' => $nextPost,
                    'prevPost' => $prevPost,
                    'path' => "radio/post"
                ];
            } else {
                $log = [
                    'id' => "",
                    'title' => "",
                    'log' => "",
                    'postDate' => "",
                    'section' => "",
                    'buildHours' => "",
                    'nextPost' => $nextPost,
                    'prevPost' => $prevPost,
                    'path' => "radio/post"
                ];
            }
        }else{
            return $this->redirectToRoute("radio/post");
        }
        if (is_null($post)){
            return $this->render('postlist.html.twig', array('log'=>$log,'filter'=>'radio','route'=>'radio'));
        }else {
            return $this->render('radio/post.html.twig', array('log' => $log,'route'=>'radio'));
        }
    }

    private function getPreviousPost($postId,$em)
    {
        $qb = $em->createQueryBuilder('u')
            ->where('u.id < :postId')
            ->setParameter(':postId',$postId)
            ->andWhere('u.sectionId IN (:filter)')
            ->setParameter(':filter',array(17))
            ->orderBy('u.postDate','DESC')
            ->setFirstResult(0)
            ->setMaxResults(1);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }

    private function getSections()
    {
        //get the section names
        $sections = $this->getDoctrine()->getRepository(Sections::class)->findAll();
        //put section results into an array
        $section_array = [];
        foreach ($sections AS $section){
            $section_array[$section->getId()] = $section->getTitle();
        }

        return $section_array;
    }

    private function getNextPost($postId,$em)
    {
        $qb = $em->createQueryBuilder('u')
//            ->select('u.id')
            ->where('u.id > :postId')
            ->setParameter(':postId',$postId)
            ->andWhere('u.sectionId IN (:filter)')
            ->setParameter(':filter',array(17))
            ->orderBy('u.postDate','ASC')
            ->setFirstResult(0)
            ->setMaxResults(1);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }
}
