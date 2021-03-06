<?php

/**
 * Shadow Daemon -- Web Application Firewall
 *
 *   Copyright (C) 2014-2015 Hendrik Buchwald <hb@zecure.org>
 *
 * This file is part of Shadow Daemon. Shadow Daemon is free software: you can
 * redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 2.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Swd\AnalyzerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swd\AnalyzerBundle\Form\Type\ProfileFilterType;
use Swd\AnalyzerBundle\Entity\ProfileFilter;
use Swd\AnalyzerBundle\Form\Type\ProfileType;
use Swd\AnalyzerBundle\Entity\Profile;
use Swd\AnalyzerBundle\Form\Type\ProfileSelectorType;
use Swd\AnalyzerBundle\Entity\Selector;

class ProfileController extends Controller
{
	public function listAction()
	{
		$em = $this->getDoctrine()->getManager();

		/* Handle filter form. */
		$filter = new ProfileFilter();
		$form = $this->createForm(new ProfileFilterType(), $filter);
		$form->handleRequest($this->get('request'));

		/* Handle the other form. */
		$profileSelector = new Selector();
		$embeddedForm = $this->createForm(new ProfileSelectorType(), $profileSelector);
		$embeddedForm->handleRequest($this->get('request'));

		if ($embeddedForm->isValid() && $this->get('request')->get('selected'))
		{
			/* Check user permissions, just in case. */
			if (false === $this->get('security.context')->isGranted('ROLE_ADMIN'))
			{
				throw $this->createAccessDeniedException('Unable to modify profiles');
			}

			foreach ($this->get('request')->get('selected') as $id)
			{
				$profile = $em->getRepository('SwdAnalyzerBundle:Profile')->find($id);

				if (!$profile)
				{
					continue;
				}

				switch ($profileSelector->getSubaction())
				{
					case 'enablelearning':
						$profile->setLearningEnabled(1);
						break;
					case 'disablelearning':
						$profile->setLearningEnabled(0);
						break;
					case 'enablewhitelist':
						$profile->setWhitelistEnabled(1);
						break;
					case 'disablewhitelist':
						$profile->setWhitelistEnabled(0);
						break;
					case 'enableblacklist':
						$profile->setBlacklistEnabled(1);
						break;
					case 'disableblacklist':
						$profile->setBlacklistEnabled(0);
						break;
					case 'deletelearning':
						$em->getRepository('SwdAnalyzerBundle:Request')->deleteByProfileAndLearning($profile, 1)->getResult();
						break;
					case 'deleteproductive':
						$em->getRepository('SwdAnalyzerBundle:Request')->deleteByProfileAndLearning($profile, 0)->getResult();
						break;
					case 'delete':
						$em->remove($profile);
						break;
				}
			}

			/* Save all the changes to the database. */
			$em->flush();

			$this->get('session')->getFlashBag()->add('info', 'The profiles were updated.');
		}

		/* Get results from database. */
		$query = $em->getRepository('SwdAnalyzerBundle:Profile')->findAllFiltered($filter);

		/* Pagination. */
		$page = $this->get('request')->query->get('page', 1);
		$limit = $this->get('request')->query->get('limit', $this->getUser()->getSetting()->getPageLimit());

		$paginator = $this->get('knp_paginator');
		$pagination = $paginator->paginate(
			$query,
			$page,
			$limit,
			array('defaultSortFieldName' => 'v.id', 'defaultSortDirection' => $this->getUser()->getSetting()->getSortOrderText())
		);

		/* Add information about existing learning cache. */
		foreach ($pagination as $profile)
		{
			$profile->setLearningRequests($em->getRepository('SwdAnalyzerBundle:Request')->countByProfileAndLearning($profile, 1)->getSingleScalarResult());
			$profile->setProductiveRequests($em->getRepository('SwdAnalyzerBundle:Request')->countByProfileAndLearning($profile, 0)->getSingleScalarResult());
		}

		/* Render template. */
		return $this->render(
			'SwdAnalyzerBundle:Profile:list.html.twig',
			array(
				'profiles' => $pagination,
				'form' => $form->createView(),
				'embeddedForm' => $embeddedForm->createView(),
				'limit' => $limit
			)
		);
	}

	/**
	 * @Security("has_role('ROLE_ADMIN')")
	 */
	public function addAction()
	{
		/* Handle form. */
		$profile = new Profile();
		$form = $this->createForm(new ProfileType(), $profile);
		$form->handleRequest($this->get('request'));

		/* Insert and redirect or show the form. */
		if ($form->isValid())
		{
			$em = $this->getDoctrine()->getManager();
			$em->persist($profile);
			$em->flush();

			$this->get('session')->getFlashBag()->add('info', 'The profile was added.');
			return $this->redirect($this->generateUrl('swd_analyzer_profiles_list'));
		}
		else
		{
			return $this->render(
				'SwdAnalyzerBundle:Profile:show.html.twig',
				array('form' => $form->createView())
			);
		}
	}

	/**
	 * @Security("has_role('ROLE_ADMIN')")
	 */
	public function editAction($id)
	{
		/* Get profile from database. */
		$profile = $this->getDoctrine()->getRepository('SwdAnalyzerBundle:Profile')->find($id);

		if (!$profile)
		{
			throw $this->createNotFoundException('No profile found for id ' . $id);
		}

		/* Handle form. */
		$form = $this->createForm(new ProfileType(), $profile);
		$form->handleRequest($this->get('request'));

		/* Update and redirect or show the form. */
		if ($form->isValid())
		{
			$profile->setDate(new \DateTime());

			$em = $this->getDoctrine()->getManager();
			$em->persist($profile);
			$em->flush();

			$this->get('session')->getFlashBag()->add('info', 'The profile was updated.');
			return $this->redirect($this->generateUrl('swd_analyzer_profiles_list'));
		}
		else
		{
			return $this->render(
				'SwdAnalyzerBundle:Profile:show.html.twig',
				array('form' => $form->createView())
			);
		}
	}
}
