<?php

namespace AppBundle\Form;

use Nucleos\ProfileBundle\Form\Type\RegistrationFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class BusinessAccountRegistrationForm extends AbstractType
{
    private $urlGenerator;
    private $tokenGenerator;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        TokenGeneratorInterface $tokenGenerator)
    {
        $this->urlGenerator = $urlGenerator;
        $this->tokenGenerator = $tokenGenerator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        switch ($options['flow_step']) {
            case 1:
                $builder->add('user', RegistrationFormType::class, [
                    'label' => false
                ]);
                break;
            case 2:
                $builder->add('businessAccount', BusinessAccountType::class, [
                    'label' => false
                ]);
				break;
            case 3:
                $builder->add('invitationLink', UrlType::class, [
                    'label' => 'registration.step.invitation.copy.link'
                ]);

                $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                    $form = $event->getForm();

                    $invitationLink = $form->get('invitationLink');
                    $config = $invitationLink->getConfig();
                    $options = $config->getOptions();

                    $generatedLink = $this->urlGenerator->generate('invitation_define_password', [
                        'code' => $this->tokenGenerator->generateToken()
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                    $options['data'] = $generatedLink;
                    $form->add('invitationLink', get_class($config->getType()->getInnerType()), $options);
                });
                break;
        }
    }

    public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults([
			'data_class' => BusinessAccountRegistration::class,
		]);
	}

    public function getBlockPrefix() : string {
		return 'businessAccountRegistration';
	}
}
