<?php 
return array(
    'username' => array(
        'not_empty' => 'Ingrese un nombre de usuario.',
        'min_length' => 'El nombre de usuario debe tener al menos: param2 caracteres.',
        'max_length' => 'The username must be less than :param2 characters long.',
        'username_available' => 'El username ya esta en uso.',
    ),
    'password' => array(
        'not_empty' => 'Debe ingresar una contraseña',
    ),
    'email' => array(
        'not_empty' => 'You must enter an email address',
        'min_length' => 'This email is too short, it must be at least :param2 characters long',
        'max_length' => 'This email is too long, it cannot exceed :param2 characters',
        'email' =>   'Please enter valid email address',
        'email_available' => 'This email address is already in use.',
    )
);
?>
