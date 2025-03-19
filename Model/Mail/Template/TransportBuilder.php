<?php

declare(strict_types=1);

namespace VladFlonta\WebApiLog\Model\Mail\Template;

use Laminas\Mime\Part;
use Magento\Framework\HTTP\Mime;
use Magento\Framework\Mail\AddressConverter;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MessageInterfaceFactory;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Magento\Framework\Mail\MimePartInterfaceFactory;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\ObjectManagerInterface;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * @var array
     */
    private array $attachments = [];

    /**
     * TransportBuilder constructor
     *
     * @param FactoryInterface $templateFactory
     * @param MessageInterface $message
     * @param SenderResolverInterface $senderResolver
     * @param ObjectManagerInterface $objectManager
     * @param TransportInterfaceFactory $mailTransportFactory
     * @param MessageInterfaceFactory|null $messageFactory
     * @param null $emailMessageInterfaceFactory
     * @param null $mimeMessageInterfaceFactory
     * @param null $mimePartInterfaceFactory
     * @param null $addressConverter
     */
    public function __construct(
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager,
        TransportInterfaceFactory $mailTransportFactory,
        MessageInterfaceFactory $messageFactory = null,
        $emailMessageInterfaceFactory = null,
        $mimeMessageInterfaceFactory = null,
        $mimePartInterfaceFactory = null,
        $addressConverter = null
    ) {
        parent::__construct(
            $templateFactory,
            $message,
            $senderResolver,
            $objectManager,
            $mailTransportFactory,
            $messageFactory,
            $emailMessageInterfaceFactory,
            $mimeMessageInterfaceFactory,
            $mimePartInterfaceFactory,
            $addressConverter
        );
        $this->emailMessageInterfaceFactory = $emailMessageInterfaceFactory ?: $this->objectManager
            ->get(EmailMessageInterfaceFactory::class);
        $this->mimeMessageInterfaceFactory = $mimeMessageInterfaceFactory ?: $this->objectManager
            ->get(MimeMessageInterfaceFactory::class);
        $this->mimePartInterfaceFactory = $mimePartInterfaceFactory ?: $this->objectManager
            ->get(MimePartInterfaceFactory::class);
        $this->addressConverter = $addressConverter ?: $this->objectManager
            ->get(AddressConverter::class);
        $this->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        parent::reset();
        $this->attachments = [];

        return $this;
    }

    /**
     * Add attachment to email
     *
     * @param string $content
     * @param string $fileName
     * @param string|null $fileType
     * @return $this
     */
    public function addAttachment(
        string $content,
        string $fileName,
        ?string $fileType = Mime::TYPE_OCTETSTREAM
    ): TransportBuilder {
        $attachmentPart = new Part();
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $attachmentPart->setContent(base64_decode($content))
            ->setType($fileType)
            ->setFileName($fileName)
            ->setDisposition(Mime::DISPOSITION_ATTACHMENT)
            ->setEncoding(Mime::ENCODING_BASE64);

        $this->attachments[] = $attachmentPart;

        return $this;
    }

    /**
     * @return \Magento\Framework\Mail\Template\TransportBuilder|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function prepareMessage()
    {
        parent::prepareMessage();

        $parts = $this->message->getBody()->getParts();
        $parts = array_merge($parts, $this->attachments);
        $messageData = [
            'encoding' => $this->message->getEncoding(),
            'subject' => $this->message->getSubject(),
            'sender' => $this->message->getSender(),
            'to' => $this->message->getTo(),
            'replyTo' => $this->message->getReplyTo(),
            'from' => $this->message->getFrom(),
            'cc' => $this->message->getCc(),
            'bcc' => $this->message->getBcc(),
        ];
        $messageData['body'] = $this->mimeMessageInterfaceFactory->create(['parts' => $parts]);
        $this->message = $this->emailMessageInterfaceFactory->create($messageData);

        return $this;
    }
}
