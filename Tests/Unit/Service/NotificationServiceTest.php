<?php
namespace DERHANSEN\SfEventMgt\Tests\Unit\Service;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use DERHANSEN\SfEventMgt\Utility\MessageType;

/**
 * Test case for class DERHANSEN\SfEventMgt\Service\NotificationService.
 *
 * @author Torben Hansen <derhansen@gmail.com>
 */
class NotificationServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{

    /**
     * @var \DERHANSEN\SfEventMgt\Service\NotificationService
     */
    protected $subject = null;

    /**
     * Setup
     *
     * @return void
     */
    protected function setUp()
    {
        $this->subject = new \DERHANSEN\SfEventMgt\Service\NotificationService();
    }

    /**
     * Teardown
     *
     * @return void
     */
    protected function tearDown()
    {
        unset($this->subject);
    }

    /**
     * Data provider for messageType
     *
     * @return array
     */
    public function messageTypeDataProvider()
    {
        return [
            'messageTypeMissing' => [
                null
            ],
            'messageTypeRegistrationNew' => [
                MessageType::REGISTRATION_NEW
            ],
            'messageTypeRegistrationConfirmed' => [
                MessageType::REGISTRATION_CONFIRMED
            ]
        ];
    }

    /**
     * @test
     * @dataProvider messageTypeDataProvider
     */
    public function sendUserMessageReturnsFalseIfIgnoreNotificationsSet($messageType)
    {
        $event = new \DERHANSEN\SfEventMgt\Domain\Model\Event();
        $registration = new \DERHANSEN\SfEventMgt\Domain\Model\Registration();
        $registration->setEmail('valid@email.tld');
        $registration->setIgnoreNotifications(true);

        $settings = ['notification' => ['senderEmail' => 'valid@email.tld']];

        $result = $this->subject->sendUserMessage($event, $registration, $settings, $messageType);
        $this->assertFalse($result);
    }

    /**
     * @test
     * @return void
     */
    public function sendUserMessageReturnsFalseIfInvalidArgumentsGiven()
    {
        $result = $this->subject->sendUserMessage(null, null, null, MessageType::REGISTRATION_NEW);
        $this->assertFalse($result);
    }

    /**
     * @test
     * @dataProvider messageTypeDataProvider
     */
    public function sendUserMessageReturnsFalseIfSendFailed($messageType)
    {
        $event = new \DERHANSEN\SfEventMgt\Domain\Model\Event();
        $registration = new \DERHANSEN\SfEventMgt\Domain\Model\Registration();
        $registration->setEmail('valid@email.tld');

        $settings = ['notification' => ['senderEmail' => 'valid@email.tld']];

        $emailService = $this->getMock('DERHANSEN\\SfEventMgt\\Service\\EmailService',
            ['sendEmailMessage'], [], '', false);
        $emailService->expects($this->once())->method('sendEmailMessage')->will($this->returnValue(false));
        $this->inject($this->subject, 'emailService', $emailService);

        // Inject configuration and configurationManager
        $configuration = [
            'plugin.' => [
                'tx_sfeventmgt.' => [
                    'view.' => [
                        'templateRootPath' => 'EXT:sf_event_mgt/Resources/Private/Templates/',
                        'layoutRootPath' => 'EXT:sf_event_mgt/Resources/Private/Layouts/'
                    ]
                ]
            ]
        ];

        $configurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager',
            ['getConfiguration'], [], '', false);
        $configurationManager->expects($this->any())->method('getConfiguration')->will(
            $this->returnValue($configuration));
        $this->inject($this->subject, 'configurationManager', $configurationManager);

        $emailView = $this->getMock('TYPO3\\CMS\\Fluid\\View\\StandaloneView', [], [], '', false);
        $objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            [], [], '', false);
        $objectManager->expects($this->once())->method('get')->will($this->returnValue($emailView));
        $this->inject($this->subject, 'objectManager', $objectManager);

        $hashService = $this->getMock('TYPO3\\CMS\\Extbase\\Security\\Cryptography\HashService');
        $hashService->expects($this->once())->method('generateHmac')->will($this->returnValue('HMAC'));
        $hashService->expects($this->once())->method('appendHmac')->will($this->returnValue('HMAC'));
        $this->inject($this->subject, 'hashService', $hashService);

        $result = $this->subject->sendUserMessage($event, $registration, $settings, $messageType);
        $this->assertFalse($result);
    }

    /**
     * @test
     * @dataProvider messageTypeDataProvider
     */
    public function sendUserMessageReturnsTrueIfSendSuccessful($messageType)
    {
        $event = new \DERHANSEN\SfEventMgt\Domain\Model\Event();
        $registration = new \DERHANSEN\SfEventMgt\Domain\Model\Registration();
        $registration->setEmail('valid@email.tld');

        $settings = ['notification' => ['senderEmail' => 'valid@email.tld']];

        $emailService = $this->getMock('DERHANSEN\\SfEventMgt\\Service\\EmailService',
            ['sendEmailMessage'], [], '', false);
        $emailService->expects($this->once())->method('sendEmailMessage')->will($this->returnValue(true));
        $this->inject($this->subject, 'emailService', $emailService);

        // Inject configuration and configurationManager
        $configuration = [
            'plugin.' => [
                'tx_sfeventmgt.' => [
                    'view.' => [
                        'templateRootPath' => 'EXT:sf_event_mgt/Resources/Private/Templates/',
                        'layoutRootPath' => 'EXT:sf_event_mgt/Resources/Private/Layouts/'
                    ]
                ]
            ]
        ];

        $configurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager',
            ['getConfiguration'], [], '', false);
        $configurationManager->expects($this->any())->method('getConfiguration')->will(
            $this->returnValue($configuration));
        $this->inject($this->subject, 'configurationManager', $configurationManager);

        $emailView = $this->getMock('TYPO3\\CMS\\Fluid\\View\\StandaloneView', [], [], '', false);
        $objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            [], [], '', false);
        $objectManager->expects($this->once())->method('get')->will($this->returnValue($emailView));
        $this->inject($this->subject, 'objectManager', $objectManager);

        $hashService = $this->getMock('TYPO3\\CMS\\Extbase\\Security\\Cryptography\HashService');
        $hashService->expects($this->once())->method('generateHmac')->will($this->returnValue('HMAC'));
        $hashService->expects($this->once())->method('appendHmac')->will($this->returnValue('HMAC'));
        $this->inject($this->subject, 'hashService', $hashService);

        $result = $this->subject->sendUserMessage($event, $registration, $settings, $messageType);
        $this->assertTrue($result);
    }

    /**
     * @test
     * @dataProvider messageTypeDataProvider
     */
    public function sendAdminNewRegistrationMessageReturnsFalseIfSendFailed($messageType)
    {
        $event = new \DERHANSEN\SfEventMgt\Domain\Model\Event();
        $registration = new \DERHANSEN\SfEventMgt\Domain\Model\Registration();

        $settings = [
            'notification' => [
                'senderEmail' => 'valid@email.tld',
                'adminEmail' => 'valid@email.tld'
            ]
        ];

        $emailService = $this->getMock('DERHANSEN\\SfEventMgt\\Service\\EmailService',
            ['sendEmailMessage'], [], '', false);
        $emailService->expects($this->once())->method('sendEmailMessage')->will($this->returnValue(false));
        $this->inject($this->subject, 'emailService', $emailService);

        // Inject configuration and configurationManager
        $configuration = [
            'plugin.' => [
                'tx_sfeventmgt.' => [
                    'view.' => [
                        'templateRootPath' => 'EXT:sf_event_mgt/Resources/Private/Templates/',
                        'layoutRootPath' => 'EXT:sf_event_mgt/Resources/Private/Layouts/'
                    ]
                ]
            ]
        ];

        $configurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager',
            ['getConfiguration'], [], '', false);
        $configurationManager->expects($this->any())->method('getConfiguration')->will(
            $this->returnValue($configuration));
        $this->inject($this->subject, 'configurationManager', $configurationManager);

        $emailView = $this->getMock('TYPO3\\CMS\\Fluid\\View\\StandaloneView', [], [], '', false);
        $objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            [], [], '', false);
        $objectManager->expects($this->once())->method('get')->will($this->returnValue($emailView));
        $this->inject($this->subject, 'objectManager', $objectManager);

        $hashService = $this->getMock('TYPO3\\CMS\\Extbase\\Security\\Cryptography\HashService');
        $hashService->expects($this->once())->method('generateHmac')->will($this->returnValue('HMAC'));
        $hashService->expects($this->once())->method('appendHmac')->will($this->returnValue('HMAC'));
        $this->inject($this->subject, 'hashService', $hashService);

        $result = $this->subject->sendAdminMessage($event, $registration, $settings, $messageType);
        $this->assertFalse($result);
    }

    /**
     * @test
     * @return void
     */
    public function sendAdminMessageReturnsFalseIfInvalidArgumentsGiven()
    {
        $result = $this->subject->sendAdminMessage(null, null, null, MessageType::REGISTRATION_NEW);
        $this->assertFalse($result);
    }

    /**
     * @test
     * @dataProvider messageTypeDataProvider
     */
    public function sendAdminNewRegistrationMessageReturnsTrueIfSendSuccessful($messageType)
    {
        $event = new \DERHANSEN\SfEventMgt\Domain\Model\Event();
        $registration = new \DERHANSEN\SfEventMgt\Domain\Model\Registration();

        $settings = [
            'notification' => [
                'senderEmail' => 'valid@email.tld',
                'adminEmail' => 'valid@email.tld'
            ]
        ];

        $emailService = $this->getMock('DERHANSEN\\SfEventMgt\\Service\\EmailService',
            ['sendEmailMessage'], [], '', false);
        $emailService->expects($this->once())->method('sendEmailMessage')->will($this->returnValue(true));
        $this->inject($this->subject, 'emailService', $emailService);

        // Inject configuration and configurationManager
        $configuration = [
            'plugin.' => [
                'tx_sfeventmgt.' => [
                    'view.' => [
                        'templateRootPath' => 'EXT:sf_event_mgt/Resources/Private/Templates/',
                        'layoutRootPath' => 'EXT:sf_event_mgt/Resources/Private/Layouts/'
                    ]
                ]
            ]
        ];

        $configurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager',
            ['getConfiguration'], [], '', false);
        $configurationManager->expects($this->any())->method('getConfiguration')->will(
            $this->returnValue($configuration));
        $this->inject($this->subject, 'configurationManager', $configurationManager);

        $emailView = $this->getMock('TYPO3\\CMS\\Fluid\\View\\StandaloneView', [], [], '', false);
        $objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            [], [], '', false);
        $objectManager->expects($this->once())->method('get')->will($this->returnValue($emailView));
        $this->inject($this->subject, 'objectManager', $objectManager);

        $hashService = $this->getMock('TYPO3\\CMS\\Extbase\\Security\\Cryptography\HashService');
        $hashService->expects($this->once())->method('generateHmac')->will($this->returnValue('HMAC'));
        $hashService->expects($this->once())->method('appendHmac')->will($this->returnValue('HMAC'));
        $this->inject($this->subject, 'hashService', $hashService);

        $result = $this->subject->sendAdminMessage($event, $registration, $settings, $messageType);
        $this->assertTrue($result);
    }

    /**
     * @test
     * @dataProvider messageTypeDataProvider
     */
    public function sendAdminMessageDoesNotSendEmailIfNotifyAdminAndNotifyOrganiserIsFalse($messageType)
    {
        $event = new \DERHANSEN\SfEventMgt\Domain\Model\Event();
        $event->setNotifyAdmin(false);
        $event->setNotifyOrganisator(false);
        $registration = new \DERHANSEN\SfEventMgt\Domain\Model\Registration();

        $settings = [
            'notification' => [
                'senderEmail' => 'valid@email.tld',
                'adminEmail' => 'valid@email.tld'
            ]
        ];

        $emailService = $this->getMock('DERHANSEN\\SfEventMgt\\Service\\EmailService',
            ['sendEmailMessage'], [], '', false);
        $emailService->expects($this->never())->method('sendEmailMessage');
        $this->inject($this->subject, 'emailService', $emailService);

        $result = $this->subject->sendAdminMessage($event, $registration, $settings, $messageType);
        $this->assertFalse($result);
    }

    /**
     * @test
     * @dataProvider messageTypeDataProvider
     */
    public function sendAdminMessageSendsEmailToOrganisatorIfConfigured($messageType)
    {
        $organisator = new \DERHANSEN\SfEventMgt\Domain\Model\Organisator();
        $event = new \DERHANSEN\SfEventMgt\Domain\Model\Event();
        $event->setNotifyAdmin(false);
        $event->setNotifyOrganisator(true);
        $event->setOrganisator($organisator);
        $registration = new \DERHANSEN\SfEventMgt\Domain\Model\Registration();

        $settings = [
            'notification' => [
                'senderEmail' => 'valid@email.tld',
                'adminEmail' => 'valid@email.tld'
            ]
        ];

        $emailService = $this->getMock('DERHANSEN\\SfEventMgt\\Service\\EmailService',
            ['sendEmailMessage'], [], '', false);
        $emailService->expects($this->once())->method('sendEmailMessage')->will($this->returnValue(true));
        $this->inject($this->subject, 'emailService', $emailService);

        // Inject configuration and configurationManager
        $configuration = [
            'plugin.' => [
                'tx_sfeventmgt.' => [
                    'view.' => [
                        'templateRootPath' => 'EXT:sf_event_mgt/Resources/Private/Templates/',
                        'layoutRootPath' => 'EXT:sf_event_mgt/Resources/Private/Layouts/'
                    ]
                ]
            ]
        ];

        $configurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager',
            ['getConfiguration'], [], '', false);
        $configurationManager->expects($this->any())->method('getConfiguration')->will(
            $this->returnValue($configuration));
        $this->inject($this->subject, 'configurationManager', $configurationManager);

        $emailView = $this->getMock('TYPO3\\CMS\\Fluid\\View\\StandaloneView', [], [], '', false);
        $objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            [], [], '', false);
        $objectManager->expects($this->once())->method('get')->will($this->returnValue($emailView));
        $this->inject($this->subject, 'objectManager', $objectManager);

        $hashService = $this->getMock('TYPO3\\CMS\\Extbase\\Security\\Cryptography\HashService');
        $hashService->expects($this->once())->method('generateHmac')->will($this->returnValue('HMAC'));
        $hashService->expects($this->once())->method('appendHmac')->will($this->returnValue('HMAC'));
        $this->inject($this->subject, 'hashService', $hashService);

        $result = $this->subject->sendAdminMessage($event, $registration, $settings, $messageType);
        $this->assertTrue($result);
    }

    /**
     * Test if the adminEmail settings get exploded and only 2 e-mails get sent
     *
     * @test
     * @dataProvider messageTypeDataProvider
     * @return void
     */
    public function sendMultipleAdminNewRegistrationMessageReturnsTrueIfSendSuccessful($messageType)
    {
        $event = new \DERHANSEN\SfEventMgt\Domain\Model\Event();
        $registration = new \DERHANSEN\SfEventMgt\Domain\Model\Registration();

        $settings = [
            'notification' => [
                'senderEmail' => 'valid@email.tld',
                'adminEmail' => 'valid1@email.tld,valid2@email.tld ,invalid-email,,'
            ]
        ];

        $emailService = $this->getMock('DERHANSEN\\SfEventMgt\\Service\\EmailService',
            ['sendEmailMessage'], [], '', false);
        $emailService->expects($this->exactly(3))->method('sendEmailMessage')->will($this->returnValue(true));
        $this->inject($this->subject, 'emailService', $emailService);

        // Inject configuration and configurationManager
        $configuration = [
            'plugin.' => [
                'tx_sfeventmgt.' => [
                    'view.' => [
                        'templateRootPath' => 'EXT:sf_event_mgt/Resources/Private/Templates/',
                        'layoutRootPath' => 'EXT:sf_event_mgt/Resources/Private/Layouts/'
                    ]
                ]
            ]
        ];

        $configurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager',
            ['getConfiguration'], [], '', false);
        $configurationManager->expects($this->any())->method('getConfiguration')->will(
            $this->returnValue($configuration));
        $this->inject($this->subject, 'configurationManager', $configurationManager);

        $emailView = $this->getMock('TYPO3\\CMS\\Fluid\\View\\StandaloneView', [], [], '', false);
        $objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
            [], [], '', false);
        $objectManager->expects($this->once())->method('get')->will($this->returnValue($emailView));
        $this->inject($this->subject, 'objectManager', $objectManager);

        $hashService = $this->getMock('TYPO3\\CMS\\Extbase\\Security\\Cryptography\HashService');
        $hashService->expects($this->once())->method('generateHmac')->will($this->returnValue('HMAC'));
        $hashService->expects($this->once())->method('appendHmac')->will($this->returnValue('HMAC'));
        $this->inject($this->subject, 'hashService', $hashService);

        $result = $this->subject->sendAdminMessage($event, $registration, $settings, $messageType);
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function sendUserMessageReturnsFalseIfNoCustomMessageGiven()
    {
        $event = new \DERHANSEN\SfEventMgt\Domain\Model\Event();
        $registration = new \DERHANSEN\SfEventMgt\Domain\Model\Registration();
        $registration->setEmail('valid@email.tld');

        $settings = ['notification' => ['senderEmail' => 'valid@email.tld']];

        $result = $this->subject->sendUserMessage($event, $registration, $settings, MessageType::CUSTOM_NOTIFICATION,
            '');
        $this->assertFalse($result);
    }

    /**
     * @test
     * @return void
     */
    public function sendCustomNotificationWithoutParameters()
    {
        $result = $this->subject->sendCustomNotification(null, '', []);
        $this->assertEquals(0, $result);
    }

    /**
     * Data provider for customNotification
     *
     * @return array
     */
    public function customNotificationDataProvider()
    {
        return [
            'noConfirmedRegistration' => [
                false,
                false,
            ],
            'ignoreNotificationsFlagSet' => [
                true,
                true
            ]
        ];
    }

    /**
     * @test
     * @dataProvider customNotificationDataProvider
     * @return void
     */
    public function sendCustomNotificationReturnsZeroIfNoConfirmedRegistrationAvailable(
        $confirmed,
        $ignoreNotifications
    ) {
        $event = new \DERHANSEN\SfEventMgt\Domain\Model\Event();

        $registration = new \DERHANSEN\SfEventMgt\Domain\Model\Registration();
        $registration->setConfirmed($confirmed);
        $registration->setIgnoreNotifications($ignoreNotifications);

        /** @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage $registrations */
        $registrations = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $registrations->attach($registration);

        $mockNotificationService = $this->getMock('DERHANSEN\\SfEventMgt\\Service\\NotificationService',
            ['sendUserMessage']);
        $mockNotificationService->expects($this->any())->method('sendUserMessage')->will($this->returnValue(true));

        $registrationRepository = $this->getMock('DERHANSEN\\SfEventMgt\\Domain\\Repository\\RegistrationRepository',
            ['findNotificationRegistrations'], [], '', false);
        $registrationRepository->expects($this->once())->method('findNotificationRegistrations')->will(
            $this->returnValue($registrations));
        $this->inject($mockNotificationService, 'registrationRepository', $registrationRepository);

        $result = $mockNotificationService->sendCustomNotification($event, 'aTemplate', ['someSettings']);
        $this->assertEquals(0, $result);
    }

    /**
     * Test that only confirmed registrations get notified. Also test, if the ignoreNotifications
     * flag is evaluated
     *
     * @test
     * @return void
     */
    public function sendCustomNotificationReturnsExpectedAmountOfNotificationsSent()
    {
        $event = new \DERHANSEN\SfEventMgt\Domain\Model\Event();

        $registration1 = new \DERHANSEN\SfEventMgt\Domain\Model\Registration();
        $registration1->setConfirmed(false);
        $registration2 = new \DERHANSEN\SfEventMgt\Domain\Model\Registration();
        $registration2->setConfirmed(true);
        $registration3 = new \DERHANSEN\SfEventMgt\Domain\Model\Registration();
        $registration3->setConfirmed(true);
        $registration4 = new \DERHANSEN\SfEventMgt\Domain\Model\Registration();
        $registration4->setConfirmed(true);
        $registration4->setIgnoreNotifications(true);

        /** @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage $registrations */
        $registrations = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $registrations->attach($registration1);
        $registrations->attach($registration2);
        $registrations->attach($registration3);
        $registrations->attach($registration4);

        $mockNotificationService = $this->getMock('DERHANSEN\\SfEventMgt\\Service\\NotificationService',
            ['sendUserMessage']);
        $mockNotificationService->expects($this->any())->method('sendUserMessage')->will($this->returnValue(true));

        $registrationRepository = $this->getMock('DERHANSEN\\SfEventMgt\\Domain\\Repository\\RegistrationRepository',
            ['findNotificationRegistrations'], [], '', false);
        $registrationRepository->expects($this->once())->method('findNotificationRegistrations')->will(
            $this->returnValue($registrations));
        $this->inject($mockNotificationService, 'registrationRepository', $registrationRepository);

        $result = $mockNotificationService->sendCustomNotification($event, 'aTemplate', ['someSettings']);
        $this->assertEquals(2, $result);
    }

    /**
     * @test
     * @return void
     */
    public function createCustomNotificationLogentryCreatesLog()
    {
        $mockLogRepo = $this->getMock('DERHANSEN\\SfEventMgt\\Domain\\Repository\\CustomNotificationRepository',
            ['add'], [], '', false);
        $mockLogRepo->expects($this->once())->method('add');
        $this->inject($this->subject, 'customNotificationLogRepository', $mockLogRepo);

        $event = new \DERHANSEN\SfEventMgt\Domain\Model\Event();
        $this->subject->createCustomNotificationLogentry($event, 'A description', 1);
    }
}
