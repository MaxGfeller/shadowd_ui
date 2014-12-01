<?php

/*
 * Shadow Daemon -- High-Interaction Web Honeypot
 *
 *   Copyright (C) 2014 Hendrik Buchwald <hb@zecure.org>
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

namespace Swd\AnalyzerBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProfileType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('serverIP', null, array('label' => 'Server IP'))
			->add('name')
			->add('key')
			->add('learningEnabled', 'choice', array('label' => 'Learning', 'choices' => array('1' => 'Enabled', '0' => 'Disabled')))
			->add('whitelistEnabled', 'choice', array('label' => 'Whitelist', 'choices' => array('1' => 'Enabled', '0' => 'Disabled')))
			->add('blacklistEnabled', 'choice', array('label' => 'Blacklist', 'choices' => array('1' => 'Enabled', '0' => 'Disabled')))
			->add('threshold', null, array('label' => 'Blacklist Threshold'))
			->add('actions', 'form_actions', array('buttons' => array('save' => array('type' => 'submit'), 'reset' => array('type' => 'reset'))));
	}

	public function getName()
	{
		return 'profile';
	}
}
