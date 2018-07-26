<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Planning;
use App\Form\ProjectType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/project")
 */
class ProjectController extends Controller
{
	/**
	 * @Route("/", name="project_index")
	 */
	public function index(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);

		$project = new Project();
		$form = $this->createForm(ProjectType::class,$project);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			if(!$this->get('session')->get('user')->isAdmin()){
				throw $this->createNotFoundException("Cette page n'existe pas");
			}
			$project = $form->getData();
			$em->persist($project);
			$em->flush();
			$this->addFlash('success','Projet ajouté');
		}

		$projects = $projectRepository->findAll();
		$sortedProjects = array(array(),array(),array(),array(),array(),array(),array(),array());
		$internalProjects = array();
		foreach($projects as $project){
			if(!$project->isBillable() && $project->getStatus() != 7){
				$internalProjects[] = $project;
			}else{
				$sortedProjects[$project->getStatus()][] = $project;
			}
		}

		return $this->render('project/index.html.twig',array('projects'=>$sortedProjects,'internalProjects'=>$internalProjects,'form'=>$form->createView()));
	}

	/**
	 * @Route("/{projectId}", name="project_view", defaults={"projectId"=0},requirements={"projectId"="\d+"})
	 */
	public function view($projectId){
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);

		if(!($project = $projectRepository->find($projectId))){
			$this->addFlash('danger','Erreur : projet non trouvé');
			return $this->redirectToRoute('project_index');
		}

		$plannings = $planningRepository->findBy(
			array('project'=>$projectId),
			array('startDate'=>'ASC','startHour'=>'ASC'));

		$users=array();
		$userIds=array();
		foreach($plannings as $planning){
			if(!in_array($planning->getUser()->getId(),$userIds)){
				$users[] = $planning->getUser();
				$userIds[] = $planning->getUser()->getId();
			}
		}
		if(isset($plannings[0])){
			$startDateObj = $plannings[0]->getStartDate();
		}else{
			$startDateObj = new \DateTime();
		}
		$baseYear = intval($startDateObj->format('Y'));
		$holidays = array();
		for($i=-1;$i<=1;$i++){
			$year=$baseYear+$i;
			$easterDate  = \easter_date($year);
			$easterDay   = date('j', $easterDate);
			$easterMonth = date('n', $easterDate);
			$easterYear  = date('Y', $easterDate);

				// Dates fixes
			$holidays[] = mktime(0, 0, 0, 1,  1,  $year);  // 1er janvier
			$holidays[] = mktime(0, 0, 0, 5,  1,  $year);  // Fête du travail
			$holidays[] = mktime(0, 0, 0, 5,  8,  $year);  // Victoire des alliés
			$holidays[] = mktime(0, 0, 0, 7,  14, $year);  // Fête nationale
			$holidays[] = mktime(0, 0, 0, 8,  15, $year);  // Assomption
			$holidays[] = mktime(0, 0, 0, 11, 1,  $year);  // Toussaint
			$holidays[] = mktime(0, 0, 0, 11, 11, $year);  // Armistice
			$holidays[] = mktime(0, 0, 0, 12, 25, $year);  // Noel

				// Dates variables
			$holidays[] = mktime(0, 0, 0, $easterMonth, $easterDay + 1,  $easterYear);
			$holidays[] = mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear);
			$holidays[] = mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear);
		}
		sort($holidays);

		return $this->render('project/view.html.twig',array('project'=>$project,'plannings'=>$plannings,'users'=>$users,'holidays'=>$holidays));
	}


	/**
	 * @Route("/edit/{projectId}",name="project_edit")
	 */
	public function edit(Request $request,$projectId){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);

		if(!($project = $projectRepository->find($projectId))){
			$this->addFlash('danger','Erreur : projet non trouvé');
			return $this->redirectToRoute('project_index');
		}

		$form = $this->createForm(ProjectType::class,$project);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$project = $form->getData();
			$em->persist($project);
			$em->flush();
			$this->addFlash('success','Projet mis à jour');
			return $this->redirectToRoute('project_view',['projectId'=>$projectId]);
		}
		return $this->render('project/edit.html.twig',array('project'=>$project,'form'=>$form->createView()));
	}

	/**
	 * @Route("/archive/{projectId}",name="project_archive")
	 */
	public function archive(Request $request,$projectId){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);

		if(!($project = $projectRepository->find($projectId))){
			$this->addFlash('danger','Erreur : projet non trouvé');
			return $this->redirectToRoute('project_index');
		}

		if($project->getStatus() == 7){
			$project->setStatus(0);
			$em->flush();
			$this->addFlash('success','Projet désarchivé');
		}else{
			$project->setStatus(7);
			$em->flush();
			$this->addFlash('success','Projet archivé');
		}
		return $this->redirectToRoute('project_index');
	}

	/**
	 * @Route("/m/{projectId}/{way}", name="project_movelink", defaults={"projectId"=0},requirements={"projectId"="\d+"})
	 */
	public function moveLink(Request $request,$projectId,$way){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$project = $projectRepository->find($projectId);
		if($project){
			if($way == "inc" && $project->getStatus() < 6){
				$project->setStatus($project->getStatus()+1);
			}elseif($way == "dec" && $project->getStatus() > 0){
				$project->setStatus($project->getStatus()-1);
			}
			$em->flush();
		}else{
			$this->addFlash('danger','Erreur : projet non trouvé');
		}
		return $this->redirectToRoute('project_index');
	}

	/**
	 * @Route("/move/{projectId}/{newStatus}",name="project_move")
	 */
	public function move(Request $request,$projectId,$newStatus){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);

		if(!($project = $projectRepository->find($projectId))){
			$arrData = ['success' => false, 'errormsg' => 'Projet non trouvé'];
		}else{
			if($newStatus < 0) $newStatus = 0;
			if($newStatus > 7) $newStatus = 7;
			$project->setStatus($newStatus);
			$em->flush();
			$arrData = ['success' => true];
		}
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/del/{projectId}",name="project_del")
	 */
	public function del(Request $request,$projectId){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);

		if(!($project = $projectRepository->find($projectId))){
			$this->addFlash('danger','Projet inexistant');
		}else{
			if($project->getStatus() == 7){
				$em->remove($project);
				$em->flush();
				$this->addFlash('success','Projet supprimé');
			}else{
				$this->addFlash('warning','Vous devez archiver un projet avant de le supprimer');
			}
		}
		return $this->redirectToRoute('project_index');
	}

}
