<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Sylius\Bundle\UserBundle\Form\Type;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @author Łukasz Chruściel <lukasz.chrusciel@lakion.com>
 */
class UserRequestPasswordResetTypeSpec extends ObjectBehavior
{
    function it_extends_abstract_type()
    {
        $this->shouldHaveType(AbstractType::class);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Sylius\Bundle\UserBundle\Form\Type\UserRequestPasswordResetType');
    }

    function it_has_name()
    {
        $this->getName()->shouldReturn('sylius_user_request_password_reset');
    }

    function it_builds_form(FormBuilderInterface $builder)
    {
        $builder->add('email', 'email', Argument::any())->shouldBeCalled()->willReturn($builder);

        $this->buildForm($builder, array());
    }
}
