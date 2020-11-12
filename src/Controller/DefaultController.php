<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Images;
use App\Entity\Posts;
use App\Entity\Sections;
use App\Entity\Config;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\GalleryService;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request, \Swift_Mailer $mailer)
    {
        // maintenance flag. set to true to display maintenance page
        $config = $this->getDoctrine()->getRepository(Config::class)->findOneBy(array());
        $maintenance = $config->getMaintenance();
        if ($maintenance) {
            return self::render('default/index.html.twig', ['route' => 'homepage']);
        }

        //contact me form
        $email = [];

        $email_form = $this->createFormBuilder($email)
            ->setAction($this->generateUrl('homepage') . "#contact")
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->add('message', TextareaType::class)
            ->add('submit', SubmitType::class, array('label' => ' Send Message'))
            ->getForm();

        $email_form->handleRequest($request);

        if ($email_form->isSubmitted() && $email_form->isValid()) {

            $secret = "6Lf16lkUAAAAALJ19UQFM-ygeNZwInfIw0xBETbL";

            $email_data = $email_form->getData();

            $recaptcha = $_POST['g-recaptcha-response'];

            $url = "https://www.google.com/recaptcha/api/siteverify";
            $data = [
                'secret' => $secret,
                'remoteip' => $_SERVER['REMOTE_ADDR'],
                'response' => $recaptcha
            ];

            $options = [
                'http' => array (
                    'method' => 'POST',
                    'content' => http_build_query($data)
                )
            ];

            $context = stream_context_create($options);

            $verify = file_get_contents($url, false, $context);

            $response = json_decode($verify);

            if($response->success === true){
                $message = (new \Swift_Message("Contact Email"))
                    ->setFrom($email_data['email'])
                    ->setTo('lyle.crane@lylecrane.com')
                    ->setBody(
                        $this->renderView(
                            'default/email.html.twig',
                            array(
                                'name' => $email_data['name'],
                                'email' => $email_data['email'],
                                'message' => $email_data['message']
                            )
                        ),
                        'text/html'
                    )/*
                 * If you also want to include a plaintext version of the message
                ->addPart(
                    $this->renderView(
                        'Emails/registration.txt.twig',
                        array('name' => $name)
                    ),
                    'text/plain'
                )
                */
                ;

                $mailer->send($message);

                $this->addFlash('notice', 'Email successfully sent!');

            }else{
                $this->addFlash('notice', 'reCaptcha failure');
            }




        }

        // gallery service
        $gallery = new GalleryService($this->getDoctrine()->getManager());

        return $this->render('default/default.html.twig', [
            'filter' => null,
            'page' => 1,
            'email_form' => $email_form->createView(),
            'route' => 'homepage',
            "slides"    =>  $gallery->carouselAction(),
            'last_post'  =>  $gallery->lastpostAction()
        ]);
    }

    /**
     * @Route("/admin/{postId}", name="admin")
     */
    public function adminAction(Request $request, $postId = null)
    {
        // restrict access to logged in user only
        $session = $request->getSession();
        $cookie = $request->cookies;

        if ($session->getId() != $cookie->get('login')){
            return $this->redirectToRoute('login');
        }

        $image = new Images();
        $update = new Images();
        if (is_null($postId) || !is_numeric($postId)) {
            $post = new Posts();
        } else {
            $post = $this->getDoctrine()->getRepository(Posts::class)->find($postId);
            if (is_null($post)) {
                $post = new Posts();
            }
        }
        $repository = $this->getDoctrine()->getRepository(Config::class);
        $query = $repository->createQueryBuilder('p')
            ->getQuery();
        $config = $query->getOneOrNullResult();

        $config_form = $this->get('form.factory')->createNamedBuilder('config_form', 'Symfony\Component\Form\Extension\Core\Type\FormType', $config)
            ->add('maintenance', ChoiceType::class, ['label' => 'Down for maintenance', 'required' => false, 'choices' => ['Yes' => 1, 'No' => 0]])
            ->add('carousel', HiddenType::class)
            ->add('submit', SubmitType::class)
            ->getForm();

        $sections = $this->getDoctrine()->getRepository(Sections::class)->findAll();
        //put the result into an array
        $section_array = [];
        foreach ($sections AS $section) {
            $section_array[$section->getTitle()] = $section->getId();
        }

        $image_form = $this->get('form.factory')->createNamedBuilder('image_form', 'Symfony\Component\Form\Extension\Core\Type\FormType', $image)
            ->add('postDate', TextType::class, array('label' => 'Image Date'))
            ->add('fileName', FileType::class)
            ->add('imageDescription', TextareaType::class)
            ->add('sectionsId', ChoiceType::class, array(
                'choices' => $section_array
            ))
            ->add('submit', SubmitType::class)
            ->getForm();

        $update_form = $this->get('form.factory')->createNamedBuilder('update_form', 'Symfony\Component\Form\Extension\Core\Type\FormType', $update)
            ->add('submit', SubmitType::class)
            ->getForm();

        $post_form = $this->get('form.factory')->createNamedBuilder('post_form', 'Symfony\Component\Form\Extension\Core\Type\FormType', $post)
            ->add('postDate', TextType::class, array('label' => 'Log Date'))
            ->add("log", TextareaType::class)
            ->add("title", TextType::class)
            ->add('sectionId', ChoiceType::class, array(
                'choices' => $section_array
            ))
            ->add('buildHours', TextType::class)
            ->add('submit', SubmitType::class)
            ->getForm();

        $image_form->handleRequest($request);

        $update_form->handleRequest($request);

        $post_form->handleRequest($request);

        $config_form->handleRequest($request);

        if ($image_form->isSubmitted() && $image_form->isValid()) {

            $image = $image_form->getData();

            $files = $request->files->get('image_form')['fileName'];
            $fileName = $files->getClientOriginalName();
            $sectionId = $image->getSectionsId();
            if ($sectionId < 15) {
                $dir = "airplane";
            } elseif ($sectionId == 16) {
                $dir = "brewing";
            } else {
                $dir = "radio";
            }

            // move file to correct directory
            if (!in_array(@$_SERVER['REMOTE_ADDR'], array(
                '127.0.0.1',
                '::1',
            ))
            ) {
                $firstDestination = realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR . "public_html/images/" . $dir . DIRECTORY_SEPARATOR . $fileName;
                $secondDestination = realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR . "public/images/" . $dir . DIRECTORY_SEPARATOR . $fileName;
                $files->move(realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR . "public_html/images/" . $dir, $fileName);
                copy($firstDestination, $secondDestination);
            } else {
//                $firstDestination = realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR."web/images/".$dir.DIRECTORY_SEPARATOR.$fileName;
//                $secondDestination = realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR."web2/images/".$dir.DIRECTORY_SEPARATOR.$fileName;
                $files->move(realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR . "public/images/" . $dir, $fileName);
//                copy($firstDestination, $secondDestination);
            }


            //set values for image
            $image->setFileName($fileName);
            $image_date = date_create($image->getPostDate());
            $image->setPostDate(date_format($image_date, "Y-m-d h:i:s"));

            //add image to database
            $em = $this->getDoctrine()->getManager();
            $em->persist($image);
            $em->flush();

            $this->addFlash('notice', 'Image has been sucessfully added to the database');
        }

        if ($update_form->isSubmitted() && $update_form->isValid()) {
            //scan image directory for find new images
            $images_folders = [
                "airplane",
                "brewing",
                "radio",
            ];

            foreach ($images_folders AS $image_folder) {
                //store update images
                $update_set = [];

                //get all the images from the folder
                $images = array_diff(scandir($_SERVER['DOCUMENT_ROOT'] . "/images/" . $image_folder), array('..', '.', '.DS_Store', 'Thumbs.db'));

                //get the section id's for the selected folder
                switch ($image_folder) {
                    case "airplane":
                        $filters = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];
                        $s_id = 0;
                        break;
                    case "brewing":
                        $filters = [16];
                        $s_id = 16;
                        break;
                    case "radio":
                        $filters = [17];
                        $s_id = 17;
                        break;
                    default:
                        $filters = [];
                }

                $repository = $this->getDoctrine()->getRepository(Images::class);

                //build query for filter
                $query = $repository->createQueryBuilder('p')
                    ->where('p.sectionsId IN (:filter)')
                    ->setParameter('filter', $filters)
                    ->getQuery();

                // get the images from the database
                $image_set = $query->getResult();

                //go through image set and put image not in images array into update_set
                $tmp_array = [];
                foreach ($image_set AS $image) {
                    $fileName = $image->getFileName();
                    $tmp_array[] = $fileName;
                }

                //put the difference into update set
                $update_set = array_diff($images, $tmp_array);

                //add new images to database
                //airplane folder set sectionId to 0
                //brewing folder set sectionId to 16
                //radio folder set sectionId to 17
                $em = $this->getDoctrine()->getManager();
                foreach ($update_set AS $update) {
                    $new_image = new image();

                    $new_image->setFileName($update);
                    $new_image->setPostDate(date("Y-m-d h:i:s"));
                    $new_image->setSectionsId($s_id);
                    $new_image->setImageDescription('Automated upload');

                    $em->persist($new_image);

                    $em->flush();
                }
            }

            $this->addFlash('notice', 'New images in image folders have been successfully added to database');

        }

        if ($post_form->isSubmitted() && $post_form->isValid()) {
            $post = $post_form->getData();

            // set values not on the form
            $post_date = date_create($post->getPostDate());
            $post->setPostDate(date_format($post_date, "Y-m-d h:i:s"));
            $post->setClicks('0');
            $post->setSlug($this->format_uri($post->getTitle()));

            //add post to database
            $em = $this->getDoctrine()->getManager();
            $em->persist($post);
            $em->flush();

            $this->addFlash('notice', 'New post has been successfully added to the database');
        }

        if ($config_form->isSubmitted() && $config_form->isValid()) {
            $post = $config_form->getData();

            //get images, titles, and texts from the form
            $images = $request->request->get('carousel_images');
            $titles = $request->request->get('carousel_titles');
            $texts = $request->request->get('carousel_text');

            //arrange the date into an array
            $carousel = [];
            foreach ($images AS $key => $value) {
                if (!empty($value)) {
                    $carousel[] = [
                        'image' => $value,
                        'title' => $titles[$key],
                        'text' => $texts[$key]
                    ];
                }
            }

            //put the edited/new data into $post
            $post->setCarousel(json_encode($carousel));

            //add post to database
            $em = $this->getDoctrine()->getManager();
            $em->persist($post);
            $em->flush();

            $this->addFlash('notice', 'Configurations table has been updated');
        }

        return $this->render('default/admin.html.twig',
            array(
                'image_form' => $image_form->createView(),
                'update_form' => $update_form->createView(),
                'post_form' => $post_form->createView(),
                'config_form' => $config_form->createView()
            )
        );
    }

    /**
     * @param null $post
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @Route("/post/{post}", name="post")
     * @Route("/post/{post}", name="/post")
     */
    public function postAction($post = null)
    {
        // maintenance flag. set to true to display maintenance page
        $config = $this->getDoctrine()->getRepository(Config::class)->findAll();
        $maintenance = $config[0]->getMaintenance();
        if ($maintenance) {
            return self::render('default/index.html.twig', ['route' => 'homepage']);
        }

        $em = $this->getDoctrine()->getRepository(Posts::class);

        if (is_null($post)) {
            $result = [];
        } elseif (is_numeric($post)) {
            //get the post by postId
            $result = $em->find($post);
        } else {
            //get the post by the slug
            $result = $em->findOneBy(array('slug' => $post));
        }

        $sections = $this->getSections();

        if (is_array($result)) {
            $log = [
                'path'  =>  "homepage"
            ];
        } elseif (!is_null($result)) {
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
                    'path' => "brewing/post"
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
                    'path' => "brewing/post"
                ];
            }
        } else {
            return $this->redirectToRoute("post");
        }

        if (is_null($post)) {
            return $this->render('postlist.html.twig', array('log' => $log, 'filter' => null, 'route' => 'homepage'));
        } else {
            return $this->render('default/post.html.twig', array('log' => $log, 'route' => 'homepage'));
        }
    }

    /**
     * @Route("blog/")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     */
    public function blogAction()
    {
        return $this->redirectToRoute("post", [],301);
    }

    /**
     * @Route("projects/{dir}", name="projects", requirements={"dir"=".+"})
     */
    public function projectsAction($dir = null)
    {
        return $this->redirectToRoute("homepage", [], 301);
    }

    /**
     * @Route("projects/", name="projects/")
     */
    public function projectAction($dir = null)
    {
        return $this->redirectToRoute("homepage", [], 301);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("home")
     * @Route("Home")
     * @Route("site")
     * @Route("home/tags/")
     */
    public function homeAction()
    {
        return $this->redirectToRoute("homepage", [], 301);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("contact")
     */
    public function contactAction()
    {
        return $this->redirectToRoute("homepage",['_fragment' => 'contact'],301);
    }

    /**
     * @param null $post
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("home/post/{post}/")
     */
    public function homepostAction($post = null)
    {
        return $this->redirectToRoute("post",["post"=>$post],301);
    }

    private function format_uri( $string, $separator = '-' )
    {
        $accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
        $special_cases = array( '&' => 'and', "'" => '');
        $string = mb_strtolower( trim( $string ), 'UTF-8' );
        $string = str_replace( array_keys($special_cases), array_values( $special_cases), $string );
        $string = preg_replace( $accents_regex, '$1', htmlentities( $string, ENT_QUOTES, 'UTF-8' ) );
        $string = preg_replace("/[^a-z0-9]/u", "$separator", $string);
        $string = preg_replace("/[$separator]+/u", "$separator", $string);

        return $string;
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
}
