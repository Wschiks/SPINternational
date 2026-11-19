<?php

/*
 * Only the rules the forms in this app use; anything else falls back to English.
 */
return [
    'confirmed' => 'De bevestiging van :attribute komt niet overeen.',
    'current_password' => 'Het wachtwoord is onjuist.',
    'email' => ':Attribute moet een geldig e-mailadres zijn.',
    'exists' => 'De gekozen :attribute bestaat niet.',
    'in' => 'De gekozen :attribute is ongeldig.',
    'integer' => ':Attribute moet een geheel getal zijn.',
    'lowercase' => ':Attribute mag alleen kleine letters bevatten.',
    'max' => [
        'string' => ':Attribute mag niet meer dan :max tekens bevatten.',
    ],
    'min' => [
        'string' => ':Attribute moet minstens :min tekens bevatten.',
    ],
    'required' => ':Attribute is verplicht.',
    'string' => ':Attribute moet tekst zijn.',
    'unique' => ':Attribute is al in gebruik.',

    'attributes' => [
        'current_password' => 'huidig wachtwoord',
        'email' => 'e-mailadres',
        'name' => 'naam',
        'password' => 'wachtwoord',
    ],
];
