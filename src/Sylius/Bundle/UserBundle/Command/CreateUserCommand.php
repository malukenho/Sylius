<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\UserBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\UserInterface;
use Sylius\Component\Rbac\Model\RoleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Aram Alipoor <aram.alipoor@gmail.com>
 */
class CreateUserCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('sylius:user:create')
            ->setDescription('Creates a new user account.')
            ->setDefinition(array(
                new InputArgument('email', InputArgument::REQUIRED, 'Email'),
                new InputArgument('password', InputArgument::REQUIRED, 'Password'),
                new InputArgument('roles', InputArgument::IS_ARRAY, 'RBAC roles'),
                new InputOption('super-admin', null, InputOption::VALUE_NONE, 'Set the user as a super admin'),
                new InputOption('disabled', null, InputOption::VALUE_NONE, 'Set the user as a disabled user'),
            ))
            ->setHelp(<<<EOT
The <info>%command.name%</info> command creates a new user account.
EOT
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $roles = $input->getArgument('roles');
        $superAdmin = $input->getOption('super-admin');
        $disabled = $input->getOption('disabled');

        $securityRoles = ['ROLE_USER'];
        if ($superAdmin) {
            $securityRoles[] = 'ROLE_ADMINISTRATION_ACCESS';
        }

        $user = $this->createUser(
            $email,
            $password,
            !$disabled,
            $roles,
            $securityRoles
        );

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $output->writeln(sprintf('Created user <comment>%s</comment>', $email));
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('email')) {
            $email = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please enter an email:',
                function($username) {
                    if (empty($username)) {
                        throw new \Exception('Email can not be empty');
                    }
                    return $username;
                }
            );

            $input->setArgument('email', $email);
        }

        if (!$input->getArgument('password')) {
            $password = $this->getHelper('dialog')->askHiddenResponseAndValidate(
                $output,
                'Please choose a password:',
                function($password) {
                    if (empty($password)) {
                        throw new \Exception('Password can not be empty');
                    }
                    return $password;
                }
            );

            $input->setArgument('password', $password);
        }

        if (!$input->getArgument('roles')) {
            $roles = $this->getHelper('dialog')->ask(
                $output,
                'Please enter user\'s roles (separated by space):'
            );

            if (!empty($roles)) {
                $input->setArgument('roles', explode(' ', $roles));
            }
        }
    }

    /**
     * @param string $email
     * @param string $password
     * @param bool $enabled
     * @param array $roles
     * @param array $securityRoles
     *
     * @return UserInterface
     */
    protected function createUser($email, $password, $enabled, array $roles, array $securityRoles = array('ROLE_USER'))
    {
        $canonicalizer = $this->getContainer()->get('sylius.user.canonicalizer');

        /**
         * @var $user UserInterface
         * @var $customer CustomerInterface
         */
        $user = $this->getUserFactory()->createNew();
        $customer = $this->getCustomerFactory()->createNew();
        $user->setCustomer($customer);
        $user->setUsername($email);
        $user->setEmail($email);
        $user->setUsernameCanonical($canonicalizer->canonicalize($user->getUsername()));
        $user->setEmailCanonical($canonicalizer->canonicalize($user->getEmail()));
        $user->setPlainPassword($password);
        $user->setRoles($securityRoles);
        $user->setEnabled($enabled);

        $roleRepository = $this->getContainer()->get('sylius.repository.role');
        foreach ($roles as $code) {
            /** @var RoleInterface $role */
            $role = $roleRepository->findOneBy(array('code' => $code));

            if (null === $role) {
                throw new \InvalidArgumentException(
                    sprintf('No role with code `%s` does not exist.', $code)
                );
            }

            $user->addAuthorizationRole($role);
        }

        $this->getContainer()->get('sylius.user.password_updater')->updatePassword($user);

        return $user;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return FactoryInterface
     */
    protected function getUserFactory()
    {
        return $this->getContainer()->get('sylius.factory.user');
    }

    /**
     * @return FactoryInterface
     */
    protected function getCustomerFactory()
    {
        return $this->getContainer()->get('sylius.factory.customer');
    }

    /**
     * @return EntityRepository
     */
    protected function getRoleRepository()
    {
        return $this->getContainer()->get('sylius.repository.role');
    }
}
