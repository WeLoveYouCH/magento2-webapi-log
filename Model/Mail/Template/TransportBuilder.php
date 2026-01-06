<?php

declare(strict_types=1);

namespace VladFlonta\WebApiLog\Model\Mail\Template;

use Laminas\Mime\Part;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Mime;
use Magento\Framework\Mail\AddressConverter;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MessageInterfaceFactory;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Magento\Framework\Mail\MimePartInterfaceFactory;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * @var array
     */
    private array $attachments = [];

    private EmailMessageInterfaceFactory $emailMessageInterfaceFactory;
    private MimeMessageInterfaceFactory $mimeMessageInterfaceFactory;
    private MimePartInterfaceFactory $mimePartInterfaceFactory;
    private AddressConverter $addressConverter;

    /**
     * TransportBuilder constructor
     *
     * @param FactoryInterface $templateFactory
     * @param MessageInterface $message
     * @param SenderResolverInterface $senderResolver
     * @param ObjectManagerInterface $objectManager
     * @param TransportInterfaceFactory $mailTransportFactory
     * @param MessageInterfaceFactory|null $messageFactory
     * @param EmailMessageInterfaceFactory|null $emailMessageInterfaceFactory
     * @param MimeMessageInterfaceFactory|null $mimeMessageInterfaceFactory
     * @param MimePartInterfaceFactory|null $mimePartInterfaceFactory
     * @param AddressConverter|null $addressConverter
     */
    public function __construct(
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager,
        TransportInterfaceFactory $mailTransportFactory,
        MessageInterfaceFactory $messageFactory = null,
        EmailMessageInterfaceFactory $emailMessageInterfaceFactory = null,
        MimeMessageInterfaceFactory $mimeMessageInterfaceFactory = null,
        MimePartInterfaceFactory $mimePartInterfaceFactory = null,
        AddressConverter $addressConverter = null
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
    public function reset(): TransportBuilder
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
     * @throws LocalizedException
     */
    protected function prepareMessage(): TransportBuilder
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
