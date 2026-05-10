<?php
if (function_exists("socket_create")) {
  echo "socket_create";
} else {
  echo "Not enable";
}