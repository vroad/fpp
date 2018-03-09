<?php
/**
 * This file is part of prolic/fpp.
 * (c) 2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fpp;

function isScalarConstructor(Constructor $constructor): bool
{
    return in_array($constructor->name(), ['String', 'Int', 'Float', 'Bool'], true);
}

function defaultDerivingMap(): array
{
    return [
        'AggregateChanged' => new Deriving\AggregateChanged(),
        'Command' => new Deriving\Command(),
        'DomainEvent' => new Deriving\DomainEvent(),
        'Enum' => new Deriving\Enum(),
        'Equals' => new Deriving\Equals(),
        'FromArray' => new Deriving\FromArray(),
        'FromScalar' => new Deriving\FromScalar(),
        'FromString' => new Deriving\FromString(),
        'Query' => new Deriving\Query(),
        'ToArray' => new Deriving\ToArray(),
        'ToScalar' => new Deriving\ToScalar(),
        'ToString' => new Deriving\ToString(),
        'Uuid' => new Deriving\Uuid(),
    ];
}

function defaultBuilders(): array
{
    return [
        'accessors' => Builder\buildAccessors,
        'arguments' => Builder\buildArguments,
        'class_extends' => Builder\buildClassExtends,
        'class_keyword' => Builder\buildClassKeyword,
        'class_name' => Builder\buildClassName,
        'constructor' => Builder\buildConstructor,
        'enum_options' => Builder\buildEnumOptions,
        'enum_value' => Builder\buildEnumValue,
        'equals_body' => Builder\buildEqualsBody,
        'from_array_body' => Builder\buildFromArrayBody,
        'message_name' => Builder\buildMessageName,
        'namespace' => Builder\buildNamespace,
        'payload_validation' => Builder\buildPayloadValidation,
        'properties' => Builder\buildProperties,
        'scalar_type' => Builder\buildScalarType,
        'setters' => Builder\buildSetters,
        'static_constructor_body' => Builder\buildStaticConstructorBody,
        'to_array_body' => Builder\buildToArrayBody,
        'to_scalar_body' => Builder\buildToScalarBody,
        'to_string_body' => Builder\buildToScalarBody,
        'traits' => Builder\buildTraits,
        'variable_name' => Builder\buildVariableName,
    ];
}

function buildReferencedClass(string $namespace, string $fqcn): string
{
    if ('' === $namespace) {
        return '\\' . $fqcn;
    }

    $position = strpos($fqcn, $namespace . '\\');

    if (false !== $position) {
        return substr($fqcn, strlen($namespace) + 1);
    }

    return '\\' . $fqcn;
}

function buildArgumentType(Argument $argument, Definition $definition): string
{
    $code = '';

    if (null === $argument->type()) {
        return $code;
    }

    if ($argument->isScalartypeHint()) {
        if ($argument->nullable()) {
            $code .= '?';
        }
        $code .= $argument->type();

        return $code;
    }

    $nsPosition = strrpos($argument->type(), '\\');

    if (false !== $nsPosition) {
        $namespace = substr($argument->type(), 0, $nsPosition);
        $name = substr($argument->type(), $nsPosition + 1);
    } else {
        $namespace = '';
        $name = $argument->type();
    }

    $returnType = $namespace === $definition->namespace()
        ? $name
        : '\\' . $argument->type();

    if ($argument->nullable()) {
        $code .= '?';
    }
    $code .= $returnType;

    return $code;
}

function buildArgumentReturnType(Argument $argument, Definition $definition): string
{
    if (null === $argument->type()) {
        return '';
    }

    return ': ' . buildArgumentType($argument, $definition);
}

function buildScalarConstructor(Definition $definition): string
{
    return "new {$definition->name()}(\$value)";
}

function buildArgumentConstructor(Argument $argument, Definition $definition, DefinitionCollection $collection): string
{
    if ($argument->isScalartypeHint() || null === $argument->type()) {
        return "\${$argument->name()}";
    }

    $nsPosition = strrpos($argument->type(), '\\');

    if (false !== $nsPosition) {
        $namespace = substr($argument->type(), 0, $nsPosition);
        $name = substr($argument->type(), $nsPosition + 1);
    } else {
        $namespace = '';
        $name = $argument->type();
    }

    if (! $collection->hasDefinition($namespace, $name)) {
        throw new \RuntimeException('Cannot build argument constructor');
    }

    $argumentDefinition = $collection->definition($namespace, $name);

    $calledClass = $namespace === $definition->namespace()
        ? $name
        : '\\' . $argument->type();

    foreach ($argumentDefinition->derivings() as $deriving) {
        switch ((string) $deriving) {
            case Deriving\Enum::VALUE:
            case Deriving\FromString::VALUE:
            case Deriving\Uuid::VALUE:
                return "$calledClass::fromString(\${$argument->name()})";
            case Deriving\FromScalar::VALUE:
                return "$calledClass::fromScalar(\${$argument->name()})";
            case Deriving\FromArray::VALUE:
                return "$calledClass::fromArray(\${$argument->name()})";
        }
    }

    throw new \RuntimeException('Cannot build argument constructor');
}

function buildScalarConstructorFromPayload(Definition $definition): string
{
    return "new {$definition->name()}(\$this->payload['value'])";
}

function buildArgumentConstructorFromPayload(Argument $argument, Definition $definition, DefinitionCollection $collection): string
{
    if ($argument->isScalartypeHint() || null === $argument->type()) {
        return "\$this->payload['{$argument->name()}']";
    }

    $nsPosition = strrpos($argument->type(), '\\');

    if (false !== $nsPosition) {
        $namespace = substr($argument->type(), 0, $nsPosition);
        $name = substr($argument->type(), $nsPosition + 1);
    } else {
        $namespace = '';
        $name = $argument->type();
    }

    if (! $collection->hasDefinition($namespace, $name)) {
        throw new \RuntimeException('Cannot build argument constructor');
    }

    $argumentDefinition = $collection->definition($namespace, $name);

    $calledClass = $namespace === $definition->namespace()
        ? $name
        : '\\' . $argument->type();

    foreach ($argumentDefinition->derivings() as $deriving) {
        switch ((string) $deriving) {
            case Deriving\Enum::VALUE:
            case Deriving\FromString::VALUE:
            case Deriving\Uuid::VALUE:
                return "$calledClass::fromString(\$this->payload['{$argument->name()}'])";
            case Deriving\FromScalar::VALUE:
                return "$calledClass::fromScalar(\$this->payload['{$argument->name()}'])";
            case Deriving\FromArray::VALUE:
                return "$calledClass::fromArray(\$this->payload['{$argument->name()}'])";
        }
    }

    throw new \RuntimeException('Cannot build argument constructor');
}
