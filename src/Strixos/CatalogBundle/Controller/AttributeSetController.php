<?php

namespace Strixos\CatalogBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Strixos\CatalogBundle\Form\Type\AttributeSetType;
use Strixos\CatalogBundle\Entity\AttributeSet;

/**
 *
 * @author Nicolas Dupont @ Strixos
 * @copyright  Copyright (c) 2012 Strixos SAS (http://www.strixos.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class AttributeSetController extends Controller
{
    /**
     * @Route("/attributeset/index")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $sets = $em->getRepository('StrixosCatalogBundle:AttributeSet')
            ->findAll();
        return $this->render('StrixosCatalogBundle:AttributeSet:index.html.twig', array('sets' => $sets));
    }

    /**
    * @Route("/attributeset/new")
    * @Template()
    */
    public function newAction(Request $request)
    {
        $set = new AttributeSet();
        $setType = new AttributeSetType();
        // set list of existing sets to prepare copy list
        $setType->setCopySetOptions($this->_getCopySetOptions());
        // prepare form
        $form = $this->createForm($setType, $set);
        // render form
        return $this->render(
            'StrixosCatalogBundle:AttributeSet:edit.html.twig', array('form' => $form->createView(),)
        );
    }

    /**
     * @Route("/attributeset/edit/{id}")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $set = $em->getRepository('StrixosCatalogBundle:AttributeSet')->find($id);
        if (!$set) {
            throw $this->createNotFoundException('No set found for id '.$id);
        }
        // set list of available attribute to prepare drag n drop list
        $setType = new AttributeSetType();
        $setType->setAvailableAttributeOptions($this->_getAvailableAttributeOptions($set));
        // prepare form
        $form = $this->createForm($setType, $set);
        // render form
        return $this->render(
            'StrixosCatalogBundle:AttributeSet:edit.html.twig', array('form' => $form->createView(),)
        );
    }

    /**
     * @Route("/attributeset/save")
     * @Template()
    */
    public function saveAction(Request $request)
    {
        // load existing object or create a new one
        $postData = $request->get('strixos_catalog_attributeset');
        $id = isset($postData['id']) ? $postData['id'] : false;
        $em = $this->getDoctrine()->getEntityManager();
        if ($id) {
            $set = $em->getRepository('StrixosCatalogBundle:AttributeSet')->find($id);
        } else {
            $copyId = isset($postData['copyfromset']) ? $postData['copyfromset'] : false;
            $setCode = isset($postData['code']) ? $postData['code'] : false;
            $copySet = $em->getRepository('StrixosCatalogBundle:AttributeSet')->find($copyId);
            $set = $copySet->copy($setCode);
        }
        // create and bind with form
        $form = $this->createForm(new AttributeSetType(), $set);
        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            // TODO problem with form validation
            //if ($form->isValid()) {
                // persist attribute set
                $em->persist($set);
                $em->flush();
                // success message and redirect
                $this->get('session')->setFlash('notice', 'Attribute set has been saved!');
                return $this->redirect(
                    $this->generateUrl('strixos_catalog_attributeset_edit', array('id' => $set->getId()))
                );
            //}
            // TODO Validation errors
        }
        // TODO Exception
    }

    /**
     * @return array
     */
    private function _getCopySetOptions()
    {
        // set list of existing sets to prepare copy list
        $em = $this->getDoctrine()->getEntityManager();
        $sets = $em->getRepository('StrixosCatalogBundle:AttributeSet')->findAll();
        $setIdToName = array();
        foreach ($sets as $set) {
            $setIdToName[$set->getId()]= $set->getCode();
        }
        return $setIdToName;
    }

    /**
    * @return array
    */
    private function _getAvailableAttributeOptions($set)
    {
        // get attribute ids TODO get from collection ?
        $attributeIds = array();
        foreach ($set->getAttributes() as $attribute) {
            $attributeIds[]= $attribute->getId();
        }
        // set list of attributes which are not in set TODO :move in custom repo
        $em = $this->getDoctrine()->getEntityManager();
        $repository = $em->getRepository('StrixosCatalogBundle:Attribute');
        $query = $repository
            ->createQueryBuilder('a')
            ->where('a.id NOT IN (:attids)')
            ->setParameter('attids', $attributeIds)
            ->getQuery();
        $attributes = $query->getResult();
        return $attributes;
    }

}
