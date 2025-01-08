``invoke``
=======

The ``invoke`` filter invokes an arrow function with the given arguments:

.. code-block:: twig

    {% set person = {first: "Bob", last: "Smith"} %}
    {% set func = p => "#{p.first} #{p.last}" %}

    {{ func|invoke(person) }}
    {# outputs Bob Smith #}

Note that the arrow function has access to the current context.

Arguments
---------

All given arguments are passed to the arrow function
