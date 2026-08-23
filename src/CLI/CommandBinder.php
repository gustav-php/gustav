<?php

namespace GustavPHP\Gustav\CLI;

use GustavPHP\Gustav\Validation\Violation;
use Symfony\Component\Console\Input\InputInterface;

/** @internal */
final readonly class CommandBinder
{
    public function __construct(private CommandDefinition $definition)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function bind(InputInterface $input): array
    {
        $bound = [];
        $violations = [];

        foreach ($this->definition->parameters as $parameter) {
            $present = $this->isPresent($input, $parameter);
            if (!$present) {
                if (!$parameter->hasDefault) {
                    $violations[] = new Violation(
                        $parameter->kind->value,
                        $this->displayPath($parameter),
                        'required',
                        'Value is required',
                    );
                }

                continue;
            }

            $value = $parameter->kind === InputKind::Argument
                ? $input->getArgument($parameter->inputName)
                : $input->getOption($parameter->inputName);
            $result = $parameter->converter->convert(
                $value,
                $parameter->kind->value,
                $this->displayPath($parameter),
            );
            if (!$result->isValid) {
                array_push($violations, ...$result->violations);

                continue;
            }

            foreach ($parameter->rules as $rule) {
                $ruleViolation = $rule->getViolation($result->value);
                if ($ruleViolation !== null) {
                    $violations[] = new Violation(
                        $parameter->kind->value,
                        $this->displayPath($parameter),
                        $ruleViolation->code,
                        $ruleViolation->message,
                    );
                }
            }

            $bound[$parameter->parameterName] = $result->value;
        }

        if ($violations !== []) {
            throw new CommandInputException($violations);
        }

        return $bound;
    }

    private function displayPath(ParameterMetadata $parameter): string
    {
        return $parameter->kind === InputKind::Option
            ? "--{$parameter->inputName}"
            : $parameter->inputName;
    }

    private function isPresent(InputInterface $input, ParameterMetadata $parameter): bool
    {
        if ($parameter->kind === InputKind::Argument) {
            $value = $input->getArgument($parameter->inputName);

            return $parameter->converter->isArray() ? $value !== [] : $value !== null;
        }

        $names = ["--{$parameter->inputName}"];
        if ($parameter->shortcut !== null) {
            $names[] = "-{$parameter->shortcut}";
        }
        if ($parameter->converter->isBoolean()) {
            $names[] = "--no-{$parameter->inputName}";
        }

        return $input->hasParameterOption($names, true);
    }
}
