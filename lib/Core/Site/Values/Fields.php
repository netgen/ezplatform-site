<?php

namespace Netgen\EzPlatformSiteApi\Core\Site\Values;

use ArrayIterator;
use eZ\Publish\API\Repository\Values\Content\Field as RepoField;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use eZ\Publish\Core\Repository\Values\ContentType\FieldDefinition as CoreFieldDefinition;
use Netgen\EzPlatformSiteApi\API\Values\Content as RepoContent;
use Netgen\EzPlatformSiteApi\API\Values\Content as SiteContent;
use Netgen\EzPlatformSiteApi\API\Values\Field as APIField;
use Netgen\EzPlatformSiteApi\API\Values\Fields as APIFields;
use Netgen\EzPlatformSiteApi\Core\Site\DomainObjectMapper;
use Netgen\EzPlatformSiteApi\Core\Site\Values\Field\NullValue;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @internal do not depend on this implementation, use API Fields instead
 *
 * @see \Netgen\EzPlatformSiteApi\Core\Site\Values\Fields
 */
final class Fields extends APIFields
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\Values\Content
     */
    private $content;

    /**
     * @var \Netgen\EzPlatformSiteApi\Core\Site\DomainObjectMapper
     */
    private $domainObjectMapper;

    /**
     * @var bool
     */
    private $failOnMissingFields;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $areFieldsInitialized = false;

    /**
     * @var \ArrayIterator
     */
    private $iterator;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\Values\Field[]
     */
    private $fieldsByIdentifier = [];

    /**
     * @var \Netgen\EzPlatformSiteApi\API\Values\Field[]
     */
    private $fieldsById = [];

    /**
     * @var \Netgen\EzPlatformSiteApi\API\Values\Field[]
     */
    private $fieldsByNumericSequence = [];

    /**
     * @param \Netgen\EzPlatformSiteApi\API\Values\Content $content
     * @param \Netgen\EzPlatformSiteApi\Core\Site\DomainObjectMapper $domainObjectMapper
     * @param bool $failOnMissingFields
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        RepoContent $content,
        DomainObjectMapper $domainObjectMapper,
        $failOnMissingFields,
        LoggerInterface $logger
    ) {
        $this->content = $content;
        $this->domainObjectMapper = $domainObjectMapper;
        $this->failOnMissingFields = $failOnMissingFields;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function getIterator()
    {
        $this->initialize();

        return $this->iterator;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function offsetExists($identifier)
    {
        $this->initialize();

        return array_key_exists($identifier, $this->fieldsByIdentifier)
            || array_key_exists($identifier, $this->fieldsByNumericSequence);
    }

    /**
     * @param $identifier
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     *
     * @return bool
     */
    public function hasField($identifier)
    {
        $this->initialize();

        return array_key_exists($identifier, $this->fieldsByIdentifier);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function offsetGet($identifier)
    {
        $this->initialize();

        if (array_key_exists($identifier, $this->fieldsByIdentifier)) {
            return $this->fieldsByIdentifier[$identifier];
        }

        if (array_key_exists($identifier, $this->fieldsByNumericSequence)) {
            return $this->fieldsByNumericSequence[$identifier];
        }

        $message = sprintf('Field "%s" in Content #%s does not exist', $identifier, $this->content->id);

        if ($this->failOnMissingFields) {
            throw new RuntimeException($message);
        }

        $this->logger->critical($message . ', using null field instead');

        return $this->getNullField($identifier, $this->content);
    }

    public function offsetSet($identifier, $value)
    {
        throw new RuntimeException('Setting the field to the collection is not allowed');
    }

    public function offsetUnset($identifier)
    {
        throw new RuntimeException('Unsetting the field from the collection is not allowed');
    }

    /**
     * {@inheritDoc}
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function count()
    {
        $this->initialize();

        return count($this->fieldsByIdentifier);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function hasFieldById($id)
    {
        $this->initialize();

        return array_key_exists($id, $this->fieldsById);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function getFieldById($id)
    {
        if ($this->hasFieldById($id)) {
            return $this->fieldsById[$id];
        }

        $message = sprintf('Field #%s in Content #%s does not exist', $id, $this->content->id);

        if ($this->failOnMissingFields) {
            throw new RuntimeException($message);
        }

        $this->logger->critical($message . ', using null field instead');

        return $this->getNullField((string)$id, $this->content);
    }

    /**
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function __debugInfo()
    {
        $this->initialize();

        return array_values($this->fieldsByIdentifier);
    }

    /**
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    private function initialize()
    {
        if ($this->areFieldsInitialized) {
            return;
        }

        $content = $this->content;

        foreach ($content->innerContent->getFieldsByLanguage($content->languageCode) as $apiField) {
            $field = $this->mapField($apiField, $content);

            $this->fieldsByIdentifier[$field->fieldDefIdentifier] = $field;
            $this->fieldsById[$field->id] = $field;
            $this->fieldsByNumericSequence[] = $field;
            $this->iterator = new ArrayIterator($this->fieldsByIdentifier);
        }

        $this->areFieldsInitialized = true;
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Field $apiField
     * @param \Netgen\EzPlatformSiteApi\API\Values\Content $content
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Field
     */
    private function mapField(RepoField $apiField, SiteContent $content): APIField
    {
        $fieldDefinition = $content->contentInfo->innerContentType->getFieldDefinition($apiField->fieldDefIdentifier);

        if ($fieldDefinition instanceof FieldDefinition) {
            return $this->domainObjectMapper->mapField($apiField, $fieldDefinition, $content);
        }

        $message = sprintf(
            'Field "%s" in Content #%s does not have a FieldDefinition',
            $apiField->fieldDefIdentifier,
            $content->id
        );

        if ($this->failOnMissingFields) {
            throw new RuntimeException($message);
        }

        $this->logger->critical($message . ', using null field instead');

        return $this->getNullField($apiField->fieldDefIdentifier, $content);
    }

    public function getNullField(string $identifier, SiteContent $content): Field
    {
        $apiField = new RepoField([
            'id' => 0,
            'fieldDefIdentifier' => $identifier,
            'value' => new NullValue(),
            'languageCode' => $content->languageCode,
            'fieldTypeIdentifier' => 'ngnull',
        ]);

        $fieldDefinition = new CoreFieldDefinition([
            'id' => 0,
            'identifier' => $apiField->fieldDefIdentifier,
            'fieldGroup' => '',
            'position' => 0,
            'fieldTypeIdentifier' => $apiField->fieldTypeIdentifier,
            'isTranslatable' => false,
            'isRequired' => false,
            'isInfoCollector' => false,
            'defaultValue' => null,
            'isSearchable' => false,
            'mainLanguageCode' => $apiField->languageCode,
            'fieldSettings' => [],
            'validatorConfiguration' => [],
        ]);

        return new Field([
            'id' => $apiField->id,
            'fieldDefIdentifier' => $fieldDefinition->identifier,
            'value' => $apiField->value,
            'languageCode' => $apiField->languageCode,
            'fieldTypeIdentifier' => $apiField->fieldTypeIdentifier,
            'name' => '',
            'description' => '',
            'content' => $content,
            'innerField' => $apiField,
            'innerFieldDefinition' => $fieldDefinition,
            'isEmpty' => true,
        ]);
    }
}
