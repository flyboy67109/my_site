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

class AirplaneController extends AbstractController
{
    /**
     * @Route("/airplane", name="airplane")
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
        return $this->render('airplane/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
            'filter'    =>  'airplane',
            'page'  =>  1,
            'route' =>  'airplane',
            'last_post'  =>  $gallery->lastpostAction('airplane')
        ]);
    }

    /**
     * @param null $post
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/airplane/post/{post}", name="airplane/post")
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
        $sections = $this->getSections();

        if (is_null($post)){
            $result = [];
        }elseif (is_numeric($post)) {
            //get the post by postId
            $result = $em->find($post);
            //see if slug is available and 301 redirect if found
            if (!empty($result->getSlug())){
                return $this->redirectToRoute("airplane/post",["post"=>$result->getSlug()],301);
            }
        }else{
            //get the post by the slug
            $result = $em->findOneBy(array('slug'=>$post));
        }

        if (is_array($result)){
            $log = [
                'path'  => "airplane"
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
                    'path' => "airplane/post"
                ];
            } else {
                $log = [
                    'id' => "",
                    'log' => "",
                    'title' => "",
                    'postDate' => "",
                    'section' => "",
                    'buildHours' => "",
                    'nextPost' => $nextPost,
                    'prevPost' => $prevPost,
                    'path' => "airplane/post"
                ];
            }
        }else{
            return $this->redirectToRoute("airplane/post");
        }

        if (is_null($post)){
            return $this->render('postlist.html.twig', array('log'=>$log,'filter'=>'airplane','route'=>'airplane','sections'=>$sections,'section'=>0));
        }else {
            return $this->render('airplane/post.html.twig', array('log' => $log,'route'=>'airplane'));
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("airplane/logs/{id}", name="airplane_logs", requirements={"id"=".+"})
     * @Route("airplane/logs/")
     */
    public function logsAction($id=null)
    {
        return $this->redirectToRoute("airplane/post",[],301);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("airplane/images/{image}")
     * @Route("airplane/images/")
     * @Route("airplane/rss")
     */
    public function imagesAction($image=null)
    {
        return $this->redirectToRoute("airplane",[],301);
    }

    /**
     * @Route("airplane/")
     */
    public function airplanesAction()
    {
        return $this->redirectToRoute('airplane',[],301);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("airplane/section/{id}", name="airplane_section", requirements={"id"=".+"})
     */
    public function sectionAction($id=null)
    {
        return $this->redirectToRoute("airplane/post",[],301);
    }

    private function getSections()
    {
        //get the section names
        $sections = $this->getDoctrine()->getRepository(Sections::class)->findAll();
        //put section results into an array
        $section_array = [0=>"All"];
        foreach ($sections AS $section){
            if ($section->getId() != 16 && $section->getId() != 17) {
                $section_array[$section->getId()] = $section->getTitle();
            }
        }

        return $section_array;
    }

    private function getPreviousPost($postId,$em)
    {
        $qb = $em->createQueryBuilder('u')
            ->where('u.id < :postId')
            ->setParameter(':postId',$postId)
            ->andWhere('u.sectionId IN (:filter)')
            ->setParameter(':filter',array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15))
            ->orderBy('u.postDate','DESC')
            ->setFirstResult(0)
            ->setMaxResults(1);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }

    private function getNextPost($postId,$em)
    {
        $qb = $em->createQueryBuilder('u')
            ->where('u.id > :postId')
            ->setParameter(':postId',$postId)
            ->andWhere('u.sectionId IN (:filter)')
            ->setParameter(':filter',array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15))
            ->orderBy('u.postDate','ASC')
            ->setFirstResult(0)
            ->setMaxResults(1);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }
}
