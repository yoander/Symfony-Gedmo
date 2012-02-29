<?php

namespace Acme\DemoBundle\Controller;

use Acme\DemoBundle\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Acme\DemoBundle\Form\ContactType;

// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DemoController extends Controller
{
    /**
     * @Route("/", name="_demo")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route("/hello/{name}", name="_demo_hello")
     * @Template()
     */
    public function helloAction($name)
    {
        return array('name' => $name);
    }

    /**
     * @Route("/contact", name="_demo_contact")
     * @Template()
     */
    public function contactAction()
    {
        $form = $this->get('form.factory')->create(new ContactType());

        $request = $this->get('request');
        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);
            if ($form->isValid()) {
                $mailer = $this->get('mailer');
                // .. setup a message and send it
                // http://symfony.com/doc/current/cookbook/email.html

                $this->get('session')->setFlash('notice', 'Message sent!');

                return new RedirectResponse($this->generateUrl('_demo'));
            }
        }

        return array('form' => $form->createView());
    }

    /**
     * @Route("/insert-categories", name="_insert_categories")
     */
    public function insertAction()
    {
        //assumes default locale is "es"
        $translatable = $this->container->get('gedmo.listener.translatable');
        $translatable->setTranslatableLocale('es_ES');
        $food = new Entity\Category;
        $food->setTitle('Comida rápida');
        $food->setDescription('Ofrecemos todo tipo de comidadas rápidas: hamburguesas, papas fritas, refrescos, ...');

        // Translatation
        $food->addTranslation(new Entity\CategoryTranslation('en', 'title', 'Fast Food'));
        $food->addTranslation(new Entity\CategoryTranslation('en', 'description', 'We offers all kind of Fast Food: hamburger, french fries, soft drinks, etc.'));

        $fruits = new Entity\Category;
        $fruits->setTitle('Frutas');
        $fruits->setDescription('Ofrecemos frutas frescas: manzana, pera, magoes');

        $fruits->addTranslation(new Entity\CategoryTranslation('en', 'title', 'Fruits'));
        $fruits->addTranslation(new Entity\CategoryTranslation('en', 'description', 'We offers fresh fruits: apple, pear, mangoes'));

        $em = $this->get('doctrine')->getEntityManager();
        $em->persist($food);
        $em->persist($fruits);
        $em->flush();

        return new Response('Ok');

    }

    /**
     * @Route("/categories/{locale}", name="_get_categories", defaults={"locale" = "es"}))
     */
    public function getCategoriesAction($locale)
    {
        $translatable = $this->container->get('gedmo.listener.translatable');
        $translatable->setTranslatableLocale($locale);

        $em = $this->get('doctrine')->getEntityManager();
        $dql = 'SELECT c.title, c.description FROM Acme\DemoBundle\Entity\Category c';
        $query = $em->createQuery($dql);

        // set the translation query hint
        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );

        $categories = $query->getResult(); // object hydration

        return new Response(json_encode($categories));
    }

}
