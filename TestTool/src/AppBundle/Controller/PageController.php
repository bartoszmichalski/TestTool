<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Page;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use XMLReader;
use AppBundle\Entity\Url;

/**
 * Page controller.
 *
 * @Route("page")
 */
class PageController extends Controller
{
    /**
     * Lists all page entities.
     *
     * @Route("/", name="page_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $pages = $em->getRepository('AppBundle:Page')->findAll();

        return $this->render('page/index.html.twig', array(
            'pages' => $pages,
        ));
    }

    /**
     * Creates a new page entity.
     *
     * @Route("/new", name="page_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $page = new Page();
        $form = $this->createForm('AppBundle\Form\PageType', $page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pageName = $page->getName();
            $sitemapPath = $pageName;//."/sitemap.xml";
            if (@fopen($sitemapPath,"r")) {
                $xml = file_get_contents($sitemapPath);
                $sitemapXML = new XMLReader();
                $sitemapXML->xml($xml);
                $xmlList = [];
                while ($sitemapXML->read()) {
                    
                    if ($sitemapXML->name == 'loc' && $sitemapXML->readString()<>'') {
                        $fileName = $sitemapXML->readString();
                        if (!strpos($fileName,'.xml')) {
                            $isXMLFile = 0;
                        } else {
                            $isXMLFile = 1;
                        }
                        
                        $xmlList[] = $fileName;
                    }
                    
                }
                if ($isXMLFile == 1) {
                    foreach ($xmlList as $xmls) {
                        if (@fopen($xmls,"r")) {
                            $xmlSite = file_get_contents($xmls);

                            $xmlFile = new XMLReader ();
                            $xmlFile->XML($xmlSite);
                            $wwwList = [];
                            while ($xmlFile->read()) {
                                if ($xmlFile->name == 'loc' && $xmlFile->readString()<>'') {

                                    $wwwList[]= $xmlFile->readString();
                                }
                            }
                        }
                    }
                } else {
                    $wwwList = $xmlList;
                    //die(var_dump($xmlList));
                }
                
                $em = $this->getDoctrine()->getManager();
                $em->persist($page);
                $em->flush($page);
                foreach ($wwwList as $urlName) {
                    $startTime = microtime(true);
                    //die($urlName);
                    $file = @fopen($urlName, "r");//80, $errno, $errstr, 30);
                    $stopTime = microtime(true);
                    $responseTime = 0;
                    if (!$file) {
                    $responseTime= -1;    
                    } else {
                    
                    $responseTime= ($stopTime-$startTime)*1000;
                    
                    fclose($file);
                    }
                    $url = new Url;
                    $url->setPage($page);
                    $url->setName($urlName);
                    $url->setResponseTime($responseTime);
                    $page->addUrl($url);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($url);
                    $em->flush($url);
                }

                
            } else {
                die("file not exist".$sitemapPath);
            }

            return $this->redirectToRoute('page_show', array('id' => $page->getId()));
        }

        return $this->render('page/new.html.twig', array(
            'page' => $page,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a page entity.
     *
     * @Route("/{id}", name="page_show")
     * @Method("GET")
     */
    public function showAction(Page $page)
    {
        $deleteForm = $this->createDeleteForm($page);

        return $this->render('page/show.html.twig', array(
            'page' => $page,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing page entity.
     *
     * @Route("/{id}/edit", name="page_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Page $page)
    {
        $deleteForm = $this->createDeleteForm($page);
        $editForm = $this->createForm('AppBundle\Form\PageType', $page);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('page_edit', array('id' => $page->getId()));
        }

        return $this->render('page/edit.html.twig', array(
            'page' => $page,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a page entity.
     *
     * @Route("/{id}", name="page_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Page $page)
    {
        $form = $this->createDeleteForm($page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($page);
            $em->flush();
        }

        return $this->redirectToRoute('page_index');
    }

    /**
     * Creates a form to delete a page entity.
     *
     * @param Page $page The page entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Page $page)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('page_delete', array('id' => $page->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
