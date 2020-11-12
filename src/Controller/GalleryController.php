<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Images;
use App\Entity\Sections;
use App\Entity\Posts;
use App\Entity\Config;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GalleryController extends AbstractController
{
    /**
     * @param null $filter
     * @param int $page
     * @return Response
     * @Route("/gallery/{page}/{filter}", name="gallery_gallery", defaults={"page"=1,"filter"=null})
     */
    public function galleryAction($filter=null,$page=1)
    {
        //get the most current six images from the images directory
        $repository = $this->getDoctrine()->getRepository(Images::class);

        switch ($filter){
            case "airplane":
                $filters = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15];
                break;
            case "brewing":
                $filters = [16];
                break;
            case "radio":
                $filters = [17];
                break;
            default:
                $filters = [];
        }

        if(!empty($filters)){

            $count = count($repository->createQueryBuilder('p')
                ->where('p.sectionsId IN (:filter)')
                ->setParameter('filter',$filters)
                ->getQuery()->getResult());

            $query = $repository->createQueryBuilder('p')
                ->where('p.sectionsId IN (:filter)')
                ->orderBy('p.postDate','DESC')
                ->setFirstResult(($page * 6) - 6)
                ->setMaxResults(6)
                ->setParameter('filter',$filters)
                ->getQuery();

        }else{

            $count = count($repository->findAll());

            $query = $repository->createQueryBuilder('p')
                ->orderBy('p.postDate','DESC')
                ->setFirstResult(($page * 6) - 6)
                ->setMaxResults(6)
                ->getQuery();
        }

        $pages = floor($count / 6);
        if($count % 6){
            ++$pages;
        }
        //todo come up with a solution is the database returns no results by accident.
        $image_set = $query->getResult();

        $section_array = $this->getSections();

        //replace section_id with section title for each image set
        $first_photo_grid = [];
        for ($x=0;$x<=2;$x++){
            if (isset($image_set[$x])) {
                $first_photo_grid[$x] = $this->getPhotoGrid($image_set[$x], $section_array);
            }else{
                $first_photo_grid[$x] = [];
            }
        }
        $second_photo_grid = [];
        for ($x=3;$x<=5;$x++){
            if (isset($image_set[$x])) {
                $second_photo_grid[$x] = $this->getPhotoGrid($image_set[$x], $section_array);
            }else{
                $second_photo_grid[$x] = [];
            }
        }
        $images = [
            'first_photo_grid'=>$first_photo_grid,
            'second_photo_grid'=>$second_photo_grid
        ];

        return $this->render('gallery/gallery.html.twig',
            array(
                'images'=>$images,
                'pagination'=>$this->setPagination($pages,$page),
                'filter'=>$filter
            )
        );
    }

    /**
     * @return Response
     */
    public function footerAction()
    {
        //get the most recent two posts
        $repository = $this->getDoctrine()->getRepository(Posts::class);

        $query = $repository->createQueryBuilder('p')
            ->orderBy('p.postDate','DESC')
            ->setMaxResults(2)
            ->getQuery();
        //todo come up with a solution if the database retuns no results by accident
        $posts = $query->getResult();

        $section_array = $this->getSections();

        $post_array = [];
        foreach ($posts AS $post){

            //get the first 55 words less html tags
            $post_log = strip_tags($post->getLog());
            $post_arr = explode(" ", $post_log);

            if (count($post_arr) > 50) {
                $post_arr = array_slice($post_arr, 0, 50);
                $post_arr[] = "...";
            }

            $post_log = implode(" ", $post_arr);

            if ($post->getSectionId() < 16){
                $page = "airplane/post";
            }elseif ($post->getSectionId() == 16){
                $page = "brewing/post";
            }else{
                $page = "radio/post";
            }

            $post_array[] = [
                'id'    =>  $post->getId(),
                'slug'  =>  $post->getSlug(),
                'log'   =>  $post_log,
                'postDate'  =>  $post->getPostDate(),
                'section'   =>  $section_array[$post->getSectionId()],
                'buildHrs'  =>  $post->getBuildHours(),
                'page'  =>  $page
            ];
        }

        return $this->render('gallery/footer.html.twig',
            array('posts'=>$post_array)
        );
    }

    /**
     * @param null $filter
     * @param int $first
     * @return Response
     * @Route("/postlist/{filter}", name="postlist")
     */
    public function postlistAction(Request $request, $filter = null,$first = 1)
    {
        $filter = !is_null($request->query->get('filter'))?$request->query->get('filter'):$filter;
        $first = !is_null($request->query->get('first'))?$request->query->get('first'):$first;
        $section = $request->query->get('section');

        $em = $this->getDoctrine()->getRepository(Posts::class );

        // get the filter array from the filter var
        switch ($filter){
            case "airplane":
                if (empty($section)){
                    $filter_array = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15];
                }else{
                    $filter_array = [$section];
                }
                break;
            case "brewing":
                $filter_array = [16];
                break;
            case "radio":
                $filter_array = [17];
                break;
            default:
                $filter_array = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17];
        }

        //set $first to 0 if a section is selected
        $first = empty($section)?$first:$first-1;

        //get all the posts for this filter
        $qb = $em->createQueryBuilder('u')
            ->where('u.sectionId IN (:filter)')
            ->setParameter(':filter',$filter_array)
            ->orderBy('u.postDate','DESC')
            ->setFirstResult($first)
            ->setMaxResults(5);

        //todo come up with a solution if the database returns no results by accident
        $result = $qb->getQuery()->getResult();

        //get the sections
        $sections = $this->getSections();

        //put the result into an array
        $log = [];
        foreach ($result as $res){
            $fil = $filter;
            //get the first 55 words less html tags
            $post_log = strip_tags($res->getLog());
            $post_arr = explode(" ", $post_log);

            if (count($post_arr) > 50) {
                $post_arr = array_slice($post_arr, 0, 50);
                $post_arr[] = "...";
            }

            $post_log = implode(" ", $post_arr);

            //get image for post
            $base_date = date_format(date_create($res->getPostDate()),"Y-m-d");

            //get latest image when post was posted
            $repo = $this->getDoctrine()->getRepository(Images::class);

            $qry = $repo->createQueryBuilder('i')
                ->where('i.postDate <= :postDate')
                ->setParameter(':postDate',$base_date." 23:59:59")
                ->andWhere('i.postDate >= :maxDate')
                ->setParameter(':maxDate',$base_date." 00:00:00")
                ->andWhere('i.sectionsId IN (:sectionsId)')
                ->setParameter(':sectionsId',$filter_array)
                ->setFirstResult(0)
                ->setMaxResults(1)
                ->getQuery();

            $image_set = $qry->getOneOrNullResult();
            $section_id = $res->getSectionId();

            //condition if $filter is empty
            if (empty($fil)){
                switch ($section_id){
                    case 17:
                        $fil = "radio";
                        break;
                    case 16:
                        $fil = "brewing";
                        break;
                    default:
                        $fil = "airplane";
                }
            }

            if (!is_null($image_set) && is_file($_SERVER['DOCUMENT_ROOT']."/images/$fil/".$image_set->getFileName())){
                $image = $image_set->getFileName();
            }else{
                switch ($fil){
                    case "airplane":
                        $image = "1027170809.jpg";
                        break;
                    case "brewing":
                        $image = "0113181533.jpg";
                        break;
                    case "radio":
                        $image = "0106181004.jpg";
                        break;
                    default:
                        $image = "Headshot.JPG";
                }

            }

            $log[] = [
                'id' => $res->getId(),
                'title' =>  $res->getTitle(),
                'log' => $post_log,
                'postDate' => $res->getPostDate(),
                'section' => $sections[$res->getSectionId()],
                'slug'  =>  $res->getSlug(),
                'path'  =>  "$fil/post",
                'image' =>  "/images/$fil/".$image
            ];
        }

        return $this->render('gallery/postlist.html.twig', array('log'=>$log,'filter'=>$filter,'route'=>$filter));
    }

    /**
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("gallery/{page}/")
     */
    public function galleriesAction($page = 1)
    {
        return $this->redirectToRoute('gallery_gallery',['page'=>$page], 301);
    }

    /**
     * @param $image_set
     * @param $section_array
     * @return array
     */
    private function getPhotoGrid($image_set,$section_array)
    {
        $photo_array = [
            'fileName'=>"",
            'postDate'=>$image_set->getPostDate(),
            'section'=>"",
            'imageDescription'=>$image_set->getImageDescription()
        ];

        $section_id = $image_set->getSectionsId();

        $photo_array['section'] = isset($section_array[$section_id])?$section_array[$section_id]:$section_array[1];

        //set the correct directory of images based on the section_id
        switch ($section_id){
            case 0:
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
            case 10:
            case 11:
            case 12:
            case 13:
            case 14:
            case 15:
                $photo_array['fileName'] = "airplane/".$image_set->getFileName();
                break;
            case 16:
                $photo_array['fileName'] = "brewing/".$image_set->getFileName();
                break;
            case 17:
                $photo_array['fileName'] = "radio/".$image_set->getFileName();
                break;
            default:
                $photo_array['fileName'] = $image_set->getFileName();
        }

        return $photo_array;
    }

    /**
     * @param $pages
     * @param $page
     * @param int $max
     * @return array
     */
    private function setPagination($pages, $page, $max = 5)
    {
        $spread = floor($max / 2);

        $minPage = $page - $spread;
        $maxPage = $page +
            $spread;

        if ($minPage < 1){
            $minPage = 1;
            $maxPage = $max;
        }

        if ($maxPage > $pages){
            $maxPage = $pages;
        }

        return array('page'=>$page,'lastPage'=>$pages,'spread'=>$max,'minPage'=>$minPage,'maxPage'=>$maxPage);
    }

    /**
     * @return array
     */
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

//    /**
//     * @param $string
//     * @param string $separator
//     * @return mixed|string
//     */
//    private function format_uri( $string, $separator = '-' )
//    {
//        $accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
//        $special_cases = array( '&' => 'and', "'" => '');
//        $string = mb_strtolower( trim( $string ), 'UTF-8' );
//        $string = str_replace( array_keys($special_cases), array_values( $special_cases), $string );
//        $string = preg_replace( $accents_regex, '$1', htmlentities( $string, ENT_QUOTES, 'UTF-8' ) );
//        $string = preg_replace("/[^a-z0-9]/u", "$separator", $string);
//        $string = preg_replace("/[$separator]+/u", "$separator", $string);
//
//        return $string;
//    }
}