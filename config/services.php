<?php

declare(strict_types=1);

/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2025 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Sidus\EAVModelBundle\Attribute\Attribute;
use Sidus\EAVModelBundle\Attribute\AttributeRegistry;
use Sidus\EAVModelBundle\Attribute\AttributeType;
use Sidus\EAVModelBundle\Attribute\AttributeTypeRegistry;
use Sidus\EAVModelBundle\Attribute\EAVEmbedAttributeType;
use Sidus\EAVModelBundle\Attribute\EAVRelationAttributeType;
use Sidus\EAVModelBundle\Attribute\EmbedAttributeType;
use Sidus\EAVModelBundle\Attribute\IdentifierAttributeType;
use Sidus\EAVModelBundle\Attribute\RelationAttributeType;
use Sidus\EAVModelBundle\Bridge\Doctrine\EventListener\DoctrineMetadataListener;
use Sidus\EAVModelBundle\Bridge\Doctrine\EventListener\OrphanEmbedRemovalListener;
use Sidus\EAVModelBundle\Bridge\Doctrine\Repository\DataRepository;
use Sidus\EAVModelBundle\Bridge\Doctrine\Type\FamilyType;
use Sidus\EAVModelBundle\Context\ContextManager;
use Sidus\EAVModelBundle\Context\ContextManagerInterface;
use Sidus\EAVModelBundle\Family\Family;
use Sidus\EAVModelBundle\Family\FamilyRegistry;
use Sidus\EAVModelBundle\Validator\Constraint\DataValidator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    $parameters = $container->parameters();

    // Parameter defaults
    $parameters->set('sidus_eav_model.attribute.class', Attribute::class);
    $parameters->set('sidus_eav_model.family.class', Family::class);

    // ==========================================================================
    // CORE REGISTRIES
    // ==========================================================================

    $services->set(AttributeTypeRegistry::class);

    $services->set(AttributeRegistry::class)
        ->args([
            param('sidus_eav_model.attribute.class'),
            param('sidus_eav_model.context.global_mask'),
            service(AttributeTypeRegistry::class),
            service('translator')->nullOnInvalid(),
        ]);

    $services->set(FamilyRegistry::class);

    // ==========================================================================
    // CONTEXT MANAGER
    // ==========================================================================

    $services->set(ContextManager::class)
        ->args([
            param('sidus_eav_model.context.default_context'),
        ]);

    $services->alias(ContextManagerInterface::class, ContextManager::class);

    // ==========================================================================
    // ATTRIBUTE TYPES
    // ==========================================================================

    // Basic scalar types
    $services->set('sidus_eav_model.attribute_type.string', AttributeType::class)
        ->args(['string', 'stringValue'])
        ->tag('sidus.attribute_type');

    $services->set('sidus_eav_model.attribute_type.text', AttributeType::class)
        ->args(['text', 'textValue'])
        ->tag('sidus.attribute_type');

    $services->set('sidus_eav_model.attribute_type.integer', AttributeType::class)
        ->args(['integer', 'integerValue'])
        ->tag('sidus.attribute_type');

    $services->set('sidus_eav_model.attribute_type.decimal', AttributeType::class)
        ->args(['decimal', 'decimalValue'])
        ->tag('sidus.attribute_type');

    $services->set('sidus_eav_model.attribute_type.boolean', AttributeType::class)
        ->args(['boolean', 'boolValue'])
        ->tag('sidus.attribute_type');

    $services->set('sidus_eav_model.attribute_type.html', AttributeType::class)
        ->args(['html', 'textValue'])
        ->tag('sidus.attribute_type');

    // Date/time types
    $services->set('sidus_eav_model.attribute_type.date', AttributeType::class)
        ->args(['date', 'dateValue'])
        ->tag('sidus.attribute_type');

    $services->set('sidus_eav_model.attribute_type.datetime', AttributeType::class)
        ->args(['datetime', 'datetimeValue'])
        ->tag('sidus.attribute_type');

    // Special scalar types
    $services->set('sidus_eav_model.attribute_type.choice', AttributeType::class)
        ->args(['choice', 'stringValue'])
        ->tag('sidus.attribute_type');

    $services->set('sidus_eav_model.attribute_type.hidden', AttributeType::class)
        ->args(['hidden', 'stringValue'])
        ->tag('sidus.attribute_type');

    // Relation types
    $services->set('sidus_eav_model.attribute_type.data_selector', EAVRelationAttributeType::class)
        ->args(['data_selector', 'dataValue'])
        ->tag('sidus.attribute_type');

    $services->set('sidus_eav_model.attribute_type.embed', EAVEmbedAttributeType::class)
        ->args(['embed', 'dataValue'])
        ->tag('sidus.attribute_type');

    // Identifier types
    $services->set('sidus_eav_model.attribute_type.string_identifier', IdentifierAttributeType::class)
        ->args(['string_identifier', 'stringValue'])
        ->tag('sidus.attribute_type');

    $services->set('sidus_eav_model.attribute_type.integer_identifier', IdentifierAttributeType::class)
        ->args(['integer_identifier', 'integerValue'])
        ->tag('sidus.attribute_type');

    // ==========================================================================
    // DOCTRINE BRIDGE (conditional on doctrine/orm being installed)
    // ==========================================================================

    // Entity Manager alias for EAV entities
    $services->set('sidus_eav_model.entity_manager')
        ->class('Doctrine\ORM\EntityManagerInterface')
        ->factory([service('doctrine'), 'getManagerForClass'])
        ->args([param('sidus_eav_model.entity.data.class')]);

    // Data Repository
    $services->set(DataRepository::class)
        ->factory([service('sidus_eav_model.entity_manager'), 'getRepository'])
        ->args([param('sidus_eav_model.entity.data.class')]);

    // Doctrine event listeners
    $services->set(DoctrineMetadataListener::class)
        ->args([
            param('sidus_eav_model.entity.data.class'),
            param('sidus_eav_model.entity.value.class'),
        ])
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata']);

    $services->set(OrphanEmbedRemovalListener::class)
        ->args([service(FamilyRegistry::class)])
        ->tag('doctrine.event_listener', ['event' => 'preRemove']);

    // Inject FamilyRegistry into FamilyType (for Doctrine DBAL type)
    $services->set('sidus_eav_model.family_type_configurator')
        ->class(FamilyType::class)
        ->factory([FamilyType::class, 'setFamilyRegistry'])
        ->args([service(FamilyRegistry::class)])
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'priority' => 1000]);

    // ==========================================================================
    // VALIDATOR
    // ==========================================================================

    $services->set(DataValidator::class)
        ->args([
            param('sidus_eav_model.entity.data.class'),
            service(FamilyRegistry::class),
            service('translator')->nullOnInvalid(),
            service('sidus_eav_model.entity_manager'),
            service('logger')->nullOnInvalid(),
        ])
        ->tag('validator.constraint_validator');
};
