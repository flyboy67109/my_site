<?php
/**
 * Created by PhpStorm.
 * User: lyle.crane
 * Date: 10/21/19
 * Time: 1:23 PM
 */

namespace App\Service;

use App\Entity\Config;
use App\Entity\Posts;
use App\Entity\Sections;
use App\Entity\Images;
use Doctrine\ORM\EntityManagerInterface;


class GalleryService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return array|mixed
     */
    public function carouselAction()
    {
        $em = $this->em;

        // fetch the slides from the config table
        $config = $em->getRepository(Config::class)->findOneBy(array());
        $slides = $config->getCarousel();

        if (!empty($slides)) {
            $slides = json_decode($slides);
        }else{ //default if no slides are found in config table
            $slides = [
                [
                    "image" => "header.jpg",
                    "title" => "HiMax Airplane",
                    "text" => "A homebuilt single seat aircraft."
                ],
                [
                    "image" => "carboys.jpg",
                    "title" => "Carboys",
                    "text" => "Mead fermenting in one gallon carboys."

                ],
                [
                    "image" => "DeemHill.jpg",
                    "title" => "Deem Hill",
                    "text" => "From the top of Deem Hill."
                ],
                [
                    "image" => "header.old.jpg",
                    "title" => "HiMax Airplane",
                    "text" => "Homebuilt single seat aircraft."
                ],
                [
                    "image" => "bottles.jpg",
                    "title" => "Bottled mead",
                    "text" => "Cherry Melomel and Cyser bottled and ready for aging."
                ],
                [
                    "image" => "UnionHills.jpg",
                    "title" => "Union Hills",
                    "text" => "Top of Union Hills in Phoenix."
                ]
            ];

        }
        return $slides;
    }

    /**
     * @param null $filter
     * @return Response
     */
    public function lastpostAction($filter=null)
    {
        $em = $this->em;

        // get 55 word snippit of last post
        $repository = $em->getRepository(Posts::class);

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

        //query for last post
        if (empty($filters)){
            $query = $repository->createQueryBuilder('p')
                ->orderBy('p.postDate','DESC')
                ->setFirstResult(0)
                ->setMaxResults(1)
                ->getQuery();
        }else{
            $query = $repository->createQueryBuilder('p')
                ->where('p.sectionId IN (:filter)')
                ->setParameter(':filter',$filters)
                ->orderBy('p.postDate','DESC')
                ->setFirstResult(0)
                ->setMaxResults(1)
                ->getQuery();
        }

        $post_set = $query->getOneOrNullResult();

        if (!is_null($post_set)) {
            $sections = $this->getSections();

            //get the first 55 words less html tags
            $post = strip_tags($post_set->getLog());
            $post = explode(" ", $post);

            if (count($post) > 50) {
                $post = array_slice($post, 0, 50);
                $post[] = "...";
            }

            $post = implode(" ", $post);

            if ($post_set->getSectionId() < 16) {
                $page = "airplane/post";
            } elseif ($post_set->getSectionId() == 16) {
                $page = "brewing/post";
            } else {
                $page = "radio/post";
            }

            $base_date = date_format(date_create($post_set->getPostDate()),"Y-m-d");

            //get latest image when post was posted
            $repo = $em->getRepository(Images::class);

            $qry = $repo->createQueryBuilder('i')
                ->where('i.postDate <= :postDate')
                ->setParameter(':postDate',$base_date." 23:59:59")
                ->andWhere('i.postDate >= :maxDate')
                ->setParameter(':maxDate',$base_date." 00:00:00")
                ->andWhere('i.sectionsId = :sectionsId')
                ->setParameter(':sectionsId',$post_set->getSectionId())
                ->setFirstResult(0)
                ->setMaxResults(1)
                ->getQuery();

            $image_set = $qry->getOneOrNullResult();

            if (is_null($image_set)){
                $section = $post_set->getSectionId();
                //set the correct directory of images based on the section_id
                switch ($section){
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
                        $image = "/headers/header.jpg";
                        break;
                    case 16:
                        $image = "/headers/equipment.jpg";
                        break;
                    case 17:
                        $image = "/headers/UnionHills.jpg";
                        break;
                    default:
                        $image = "/headers/header.old.jpg";
                }
            }else{
                $sections = $this->getSections();
                $section = $image_set->getSectionsId();

                //set the correct directory of images based on the section_id
                switch ($section){
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
                        $image = "airplane/".$image_set->getFileName();
                        break;
                    case 16:
                        $image = "brewing/".$image_set->getFileName();
                        break;
                    case 17:
                        $image = "radio/".$image_set->getFileName();
                        break;
                    default:
                        $image = $image_set->getFileName();
                }
            }

            $last_post = [
                'id' => $post_set->getId(),
                'title' =>  trim($this->format_uri($post_set->getTitle())),
                'log' => $post,
                'postDate' => $post_set->getPostDate(),
                'section' => $sections[$post_set->getSectionId()],
                'page' => $page,
                'image' =>  $image
            ];
        }else{
            $last_post = [
                'id' => '',
                'title' =>  '',
                'log' => '',
                'postDate' => '',
                'section' => '',
                'page' => ''
            ];
        }

        return $last_post;
    }

    /**
     * @return array
     */
    private function getSections()
    {
        $em = $this->em;
        //get the section names
        $sections = $em->getRepository(Sections::class)->findAll();
        //put section results into an array
        $section_array = [];
        foreach ($sections AS $section){
            $section_array[$section->getId()] = $section->getTitle();
        }

        return $section_array;
    }

    /**
     * @param $string
     * @param string $separator
     * @return mixed|string
     */
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
}