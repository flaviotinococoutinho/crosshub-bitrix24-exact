<?php

// Nao carregar nem expor ambiente/credenciais no endpoint de diagnostico.
header('Content-Type: text/plain; charset=UTF-8');
echo "CrossHub integration\n";
