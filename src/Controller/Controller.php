<?php

namespace App\Controller;

use Symfony\Component\Form\FormTypeInterface;
use App\Entity\Cours;
use App\Entity\Commnt;
use App\Entity\Post;
use App\Entity\Favoris;
use App\Entity\Lecon;
use App\Entity\Chapitre;
use App\Entity\Commancer;
use App\Entity\Feedback;
use App\Entity\Formateur;
use App\Entity\Type;
use App\Entity\User;
use App\Repository\ChapitreRepository;
use App\Repository\CommancerRepository;
use App\Repository\CommntRepository;
use App\Repository\CoursRepository;
use App\Repository\TypeRepository;
use App\Repository\FavorisRepository;
use App\Repository\FeedbackRepository;
use App\Repository\FormateurRepository;
use App\Repository\LeconRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use PhpParser\Node\Expr\New_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Cookie;
 

class Controller extends AbstractController
{
   
    #[Route('/mainFS', name: 'mainFS')]
    public function index(CoursRepository $cr,UserRepository $ur,Request $request): Response
    {
        $cours=$cr->findBy([],['note'=> 'DESC'],3);
        $myCookie = $request->cookies->get('id');
        $user=$ur->findBy(['id'=>$myCookie]);
        return $this->render('/main/acceuil.html.twig',['c'=>$cours,'user'=>$user]);
    }
    #[Route('/login', name: 'login')]
    public function auth(EntityManagerInterface $em,UserRepository $ur,Request $request): Response
    {
        $err="";
        if($request->request->count()>0){
            if($request->request->get('bgn1')){
                $user=new User();
                if($request->request->get('nom')=="" || $request->request->get('prenom')=="" || $request->request->get('mail')=="" || $request->request->get('filiere')=="" || $request->request->get('password')==""){
                    $err="priére de remplir les champs";
                }elseif($request->request->get('password') != $request->request->get('password1')){
                    $err="les mots de passe doivent etre similaire";
                }else{
                    $user->setNom($request->request->get('nom'))
                    ->setPrenom($request->request->get('prenom'))
                    ->setMail($request->request->get('mail'))
                    ->setFiliere($request->request->get('filiere'))
                    ->setMdp($request->request->get('password'));
                    $em->persist($user);
                    $em->flush();
                    $err="ajout d'un utilisateur avec succes";
                }
                return $this->render('/main/login.html.twig',["err"=>$err]);
            }
            if($request->request->get('begin')){
                $user=$ur->findBy(['mail'=>$request->request->get('mail'),'mdp'=>$request->request->get('password')]);
                if(count($user)>0){
                    $cookie = new Cookie('id',$user[0]->getId() , time() + 3600 * 24 * 7);
                    $response = new Response();
                    $response->headers->setCookie($cookie);
                    $response->send();
                    return $this->redirectToRoute('mainFS');
                }
                else{
                    $err="verifié vos coordonnées";
                }
            }
        }
        return $this->render('/main/login.html.twig',["err"=>$err]);
    }

    #[Route('/etudiant', name: 'etud')]
    public function etudi(EntityManagerInterface $em,UserRepository $ur,Request $request,CoursRepository $cr): Response
    {
       $myCookie = $request->cookies->get('id');
        
        $query = $em->createQuery(
            'SELECT c FROM App\Entity\Commancer c WHERE c.progres >= :progress AND c.user = :user'
        )->setParameter('progress', 100)->setParameter('user', $myCookie);
        $ter = $query->getResult();
        $cours=$cr->findBy([],['note'=> 'DESC'],3);
        $cours1=$cr->findBy([],['nom'=> 'DESC'],3);
        
        $user=$ur->findBy(['id'=>$myCookie]);
        $qb = $em->createQueryBuilder();
            $qb
                ->select('c')
                ->from('App\Entity\Commancer', 'cm')
                ->join('App\Entity\Cours', 'c' ,'WITH', 'c.id=cm.id_cours')
                ->where('c.user = :user')
                ->setParameter('user', $myCookie);
    
                $res = $qb->getQuery()->getResult();
                
        return $this->render('/main/mainFS.html.twig',['res'=>$res,'c'=>$cours,'c1'=>$cours1,'ter'=>$ter]);
    }
    #[Route('/favoris', name: 'fav')]
    public function favoris(EntityManagerInterface $em,UserRepository $ur,Request $req,FavorisRepository $fr): Response
    {
        $myCookie = $req->cookies->get('id');
        $user=$ur->findBy(['id'=>$myCookie]);
        $id=$req->query->get('id');
        $fav=$req->query->get('fav');
        $fa=$fr->findBy(['id_cours'=>$id]);
        $cours=$em->createQueryBuilder()->select('c')
        ->from('App\Entity\Cours', 'c')
        ->join('App\Entity\Favoris', 'f', 'WITH', 'c.id = f.id_cours' )
        ->where('c.user = :user')
        ->setParameter('user', $myCookie);
        $courses = $cours->getQuery()->getResult();
        if($fav=='unfav'){
            foreach($fa as $ff){
                $em->remove($ff);
            }
            $em->flush();
        }
        return $this->render('/main/favoris.html.twig', ['courses' => $courses]);
    }
    #[Route('/apply', name: 'apply')]
    public function apply(Request $req,EntityManagerInterface $em): Response
    {
        $ll='yy';
        $myCookie = $req->cookies->get('id');
        if($req->request->count()>0){
            if($req->request->get('ok')){
                $ll='hi';
            $formateur=new Formateur();
            $formateur->setIdUser($myCookie);
            $em->persist($formateur);
            $em->flush();
            return $this->redirectToRoute('formateur');
            }
        }
        return $this->render('/main/enter.html.twig',['ll'=>$ll]);
    }
    #[Route('/formateur', name: 'formateur')]
    public function form(CoursRepository $cr,FormateurRepository $fr,Request $request,EntityManagerInterface $em): Response
    {
        $myCookie = $request->cookies->get('id');
        $form=$fr->findBy(['idUser'=>$myCookie]);
        if(count($form)==0){
            return $this->render('/main/enter.html.twig');
        }
        
        
        $cours=$cr->findBy(['user'=>$myCookie]);
        $fav=$request->query->get('fav');
        $id=$request->query->get('id');
        $crs=$cr->findBy(['id'=>$id]);
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder
            ->select('f')
            ->from('App\Entity\Favoris', 'f')
            ->join('App\Entity\Cours', 'c' ,'WITH', 'c.id=f.id_cours')
            ->where('c.user = :user')
            ->setParameter('user', $myCookie);

            $results = $queryBuilder->getQuery()->getResult();

            $qb = $em->createQueryBuilder();
            $qb
                ->select('c')
                ->from('App\Entity\Commancer', 'cm')
                ->join('App\Entity\Cours', 'c' ,'WITH', 'c.id=cm.id_cours')
                ->where('c.user = :user')
                ->setParameter('user', $myCookie);
    
                $res = $qb->getQuery()->getResult();
        if($fav=='unfav'){
            foreach($crs as $ff){
                $em->remove($ff);
            }
            $em->flush();
        }

        return $this->render('/main/prof.html.twig',['c'=>$cours,'like'=>$results,'res'=>$res]);
    }
    #[Route('/ajout', name: 'ajout')]
    public function ajout(EntityManagerInterface $em,Request $request)
    {
        $myCookie = $request->cookies->get('id');
        if($request->request->count()>0)
        {
            
        $cours = new Cours();
        $cours->setImage($request->request->get('image'))
        ->setNom($request->request->get('nom'))
        ->setDescr($request->request->get('descr'))
        ->setNiveau($request->request->get('niveau'))
        ->setType($request->request->get('type'))
        ->setDure($request->request->get('dure'))
        ->setNote($request->request->get('note'))
        ->setLangue($request->request->get('langue'))
        ->setUser($myCookie)
        ->setProgres(0);
        /*return $this->render(
            'tp/show.html.twig',['title'=>$article->getTitre()]);*/
            $em->persist($cours);
            $em->flush();
            return $this->redirectToRoute('ajoutLec',['id'=>$cours->getId()]);
        }
        
       return $this->render('/main/ajoutCours.html.twig');


    }
    #[Route('/ajoutLec', name: 'ajoutLec')]
    public function ajoutLec(Request $request,EntityManagerInterface $em,ChapitreRepository $chR): Response
    {  
        $id=$request->query->get('id');
       
       
        if($request->request->count()>0)
        {
            if($request->request->get('chap')){
                $chap=new Chapitre();
                $chap->setIdCours($id)
                ->setNom($request->request->get('nomCh'));
                $em->persist($chap);
                $em->flush();
               
            }elseif($request->request->get('lec')){
                $lecon= new Lecon();
                $lecon->setIdCh($request->request->get('idch'))
                ->setNom($request->request->get('nomLec'))
                ->setVideo($request->request->get('vid'))
                ->setDescr($request->request->get('descr'))
                ->setLu(0);
                $em->persist($lecon);
                $em->flush();
            }
            $chapitre=$chR->findBy(['id_cours'=>$id]);
            $lec=$em->createQueryBuilder()->select('l')
            ->from('App\Entity\Lecon', 'l')
            ->join('App\Entity\Chapitre', 'c', 'WITH', 'c.id = l.id_ch' );
            $lecs = $lec->getQuery()->getResult();
                
            return $this->render('/main/ajoutLec.html.twig',['c'=>$chapitre,'id'=>$id,'l'=>$lecs]);
        
    }
    $chapitre=$chR->findBy(['id_cours'=>$id]);
    $lec=$em->createQueryBuilder()->select('l')
    ->from('App\Entity\Lecon', 'l')
    ->join('App\Entity\Chapitre', 'c', 'WITH', 'c.id = l.id_ch' );
    $lecs = $lec->getQuery()->getResult();

        

        return $this->render('/main/ajoutLec.html.twig',['c'=>$chapitre,'l'=>$lecs]);
    }
    #[Route('/cours', name: 'cours')]
    public function cours(CoursRepository $cr,TypeRepository $tr,Request $req,PaginatorInterface $pi): Response
    {
        $type=$tr->findAll();
        $typ=$req->query->get('typ');
        
            if ($typ=='All' || $typ==null){
                $coursP=$cr->findAll();
            }
            else{
                $coursP=$cr->findBy(['type'=>$typ]);
            }
        $cours=$pi->paginate(
            $coursP,$req->query->getInt('page',1),8
        );
        return $this->render('/main/Cours.html.twig',['c'=>$cours,'t'=>$type]);
    }
    #[Route('/det_cours', name: 'det_cours')]
    public function det_cours(Request $req,FeedbackRepository $fdr,FavorisRepository $fr,ChapitreRepository $chR,CoursRepository $cr,EntityManagerInterface $em): Response
    { 
        $myCookie = $req->cookies->get('id');
        $page = $req->query->getInt('page', 1);
        $limit = 3;
        $offset = ($page - 1) * $limit;
        $id=$req->query->get('id');
       
        $favoris=new favoris();
        $fa=$fr->findBy(['id_cours'=>$id]);
        $f=$fr->count(['id_cours'=>$id]);
        $chapitre=$chR->findBy(['id_cours'=>$id]);
        $favoris->setIdCours($id)
        ->setUser($myCookie);
        $lec=$em->createQueryBuilder()->select('l')
        ->from('App\Entity\Lecon', 'l')
        ->join('App\Entity\Chapitre', 'c', 'WITH', 'c.id = l.id_ch'  )
        ->where('c.id_cours=:id')
        ->setParameter('id',$id);
        $lecs = $lec->getQuery()->getResult();
        $fav=$req->query->get('fav');
         if($fav=='fav'&& $f==0 ){ 
            $em->persist($favoris);
            $em->flush();
            
        }else if($fav=='unfav'){
            foreach($fa as $ff){
                $em->remove($ff);
            }
            $em->flush();
        }
        if($req->request->count()){
            if($req->request->get('beg'))
            {
                $feed=new Feedback();
                $feed->setComment($req->request->get('comment'))
                ->setCoursId($id)
                ->setRate($req->request->get('review'))
                ->setUser($req->request->get('nom'))
                ->setCreatAt(new \DateTimeImmutable('now'));
                $em->persist($feed);
                $em->flush();
            }
            
        }
        $feedb=$fdr->findBy(['cours_id'=>$id]); 
        $query = $em->createQuery(
            'SELECT COUNT(f.id) AS count, f.rate AS rate FROM App\Entity\Feedback f
            WHERE f.cours_id = :id
            GROUP BY f.rate'
        )->setParameter('id', $id);
        
       
        $etoile = $query->getResult();
        $fav=$req->query->get('fav');
        $cours=$cr->findBy(['id'=>$id]);
        return $this->render('/main/detcours.html.twig',['c'=>$cours,'f'=>$fa,'fd'=>$feedb,'f1'=>$f,'c1'=>$chapitre,'l'=>$lecs,'etoile'=>$etoile]);
    }

    #[Route('/lecon', name: 'lecon')]
    public function lecon(Request $req,CommancerRepository $cmR,ChapitreRepository $chR,LeconRepository $lc ,CoursRepository $cr,EntityManagerInterface $em): Response
    {
        $progres=0;
        $id=$req->query->get('id');
        $idLec=$req->query->get('idLec');
        
        $lec=$em->createQueryBuilder()->select('l')
        ->from('App\Entity\Lecon', 'l')
        ->join('App\Entity\Chapitre', 'c', 'WITH', 'c.id = l.id_ch'  )
        ->where('c.id_cours=:id')
        ->setParameter('id',$id);
        $lecs = $lec->getQuery()->getResult();
        $cont=$em->createQueryBuilder()->select('l')
        ->from('App\Entity\Lecon', 'l')
        ->join('App\Entity\Chapitre', 'c', 'WITH', 'c.id = l.id_ch'  )
        ->where('c.id_cours=:id')
        ->andWhere('l.lu=1')
        ->setParameter('id',$id);
        $cntt = $cont->getQuery()->getResult();
        
        
        $nb=count($cntt);
       
      
        
        $progres=round(100/count($lecs))*($nb);
        $prog=$em->getRepository(Cours::class)->find($id);
        $prog->setProgres($progres);
        $em->flush();
       
        if($req->request->count()){
            if($req->request->get('read')){
                $nb=count($lc->findBy(['lu'=>'1']));
                $progres=round(100/count($lecs))*($nb);
                $lu=$em->getRepository(Lecon::class)->find($idLec);
                if (!$lu) {
                    throw $this->createNotFoundException(
                        'No lesson found for id '.$id
                    );
                }
                $lu->setLu(1);
                $prog=$em->getRepository(Cours::class)->find($id);
                $prog->setProgres($progres);
                $em->flush();
                
            }
            
        }
        $myCookie = $req->cookies->get('id');
        $cours=$cr->findBy(['id'=>$id]);
        $chapitre=$chR->findBy(['id_cours'=>$id]);
        $lec=$em->createQueryBuilder()->select('l')
        ->from('App\Entity\Lecon', 'l')
        ->join('App\Entity\Chapitre', 'c', 'WITH', 'c.id = l.id_ch'  )
        ->where('c.id_cours=:id')
        ->setParameter('id',$id);
        $lecs = $lec->getQuery()->getResult();
        $detLec=$lc->findBy(['id'=>$idLec]);
        $cr_comm=$cmR->findBy(['id_cours'=>$id]);
        if (count($cr_comm)==0){
            $cr_cm=new Commancer();
            $cr_cm->setIdCours($id)
            ->setProgres($cours[0]->getProgres())
            ->setUser($myCookie);
            $em->persist($cr_cm);
            $em->flush();
        }
        return $this->render('/main/lecon.html.twig',['c'=>$chapitre,'id'=>$id,'l'=>$lecs,'cours'=>$cours,'progres'=>$progres,'detL'=>$detLec,]);
    }
    #[Route('/MesCours', name: 'mescours')]
    public function mcours(CommancerRepository $cr,Request $request,EntityManagerInterface $em): Response
    {
        $myCookie = $request->cookies->get('id');
        $qb = $em->createQueryBuilder();
        $qb
            ->select('c')
            ->from('App\Entity\Commancer', 'cm')
            ->join('App\Entity\Cours', 'c' ,'WITH', 'c.id=cm.id_cours')
            ->where('c.user = :user')
            ->setParameter('user', $myCookie);

            $cours = $qb->getQuery()->getResult();
        return $this->render('/main/mescours.html.twig',['c'=>$cours]);
    }
    #[Route('/aide', name: 'aide')]
    public function aide(CommntRepository $cr,UserRepository $ur,PostRepository $pr,EntityManagerInterface $em,Request $req): Response
    {
        $myCookie = $req->cookies->get('id');
        $user=$ur->findBy(['id'=>$myCookie]);
        if($req->request->count()){
            if($req->request->get('post')){
                $post=new Post();
                $post->setUser($user[0]->getNom())
                ->setLik(0)
                ->setText($req->request->get('text'));
                $em->persist($post);
                $em->flush();
            }
            if($req->request->get('like')){
                $post=$em->getRepository(Post::class)->find($req->request->get('id'));
                $post->setLik($post->getLik()+1);
                $em->flush();
            }
            if($req->request->get('commnt')){
                $comnt=new Commnt();
                $comnt->setUser($user[0]->getNom())
                ->setLik(0)
                ->setText($req->request->get('text'))
                ->setPostId($req->request->get('id'));
                $em->persist($comnt);
                $em->flush();
            }
            
        }
        $post=$pr->findAll();
        $comnt=$cr->findAll();
        return $this->render('/main/aide.html.twig',['post'=>$post,'comnt'=>$comnt]);
    }
}

?>
