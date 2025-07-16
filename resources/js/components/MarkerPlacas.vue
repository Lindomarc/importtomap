<template>
    <div>
      <Marker 
        v-for="placa in placas" 
        :key="placa.name" 
        :options="getMarkerOptions(placa)"
        @click="$emit('marker-clicked', placa)" 

      />
    </div>
  </template>
  
  <script setup>
  import {  Marker } from "vue3-google-map";

  // Função para criar o SVG customizado
  const createCustomSVG = (color = '#2563eb', text = 'P') => {
    return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42">
        <!-- Sombra do marcador -->
        <ellipse cx="16" cy="40" rx="8" ry="2" fill="#000000" opacity="0.2"/>
        
        <!-- Corpo principal do marcador -->
        <path d="M16 2 C10.48 2 6 6.48 6 12 C6 18 16 38 16 38 C16 38 26 18 26 12 C26 6.48 21.52 2 16 2 Z" 
              fill="${color}" 
              stroke="#ffffff" 
              stroke-width="2"/>
        
        <!-- Círculo interno -->
        <circle cx="16" cy="12" r="6" fill="#ffffff" opacity="0.9"/>
        
        <!-- Texto ou ícone no centro -->
        <text x="16" y="16" text-anchor="middle" fill="${color}" 
              font-family="Arial, sans-serif" font-size="8" font-weight="bold">${text}</text>
      </svg>
    `)}`;
  };
  
  // Função para criar ícone de cidade
  const createCityIcon = (number) => {
    return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42">
        <!-- Sombra -->
        <ellipse cx="16" cy="40" rx="8" ry="2" fill="#000000" opacity="0.2"/>
        
        <!-- Corpo do marcador -->
        <path d="M16 2 C10.48 2 6 6.48 6 12 C6 18 16 38 16 38 C16 38 26 18 26 12 C26 6.48 21.52 2 16 2 Z" 
              fill="#dc2626" 
              stroke="#ffffff" 
              stroke-width="2"/>
        
        <!-- Círculo interno -->
        <circle cx="16" cy="12" r="6" fill="#ffffff" opacity="0.9"/>
        
        <!-- Número -->
        <text x="16" y="16" text-anchor="middle" fill="#dc2626" 
              font-family="Arial, sans-serif" font-size="8" font-weight="bold">${number}</text>
      </svg>
    `)}`;
  };
  
  // Lista de cidades
const placas = [];

  
  // Função para obter as opções do marcador
  const getMarkerOptions = (placa) => {
    const baseOptions = {
      position: placa.position,
      title: placa.name,
      clickable: true,
      icon: {
        url: createCustomSVG('#2563eb', 'P'), 
      }
    };
 
    return baseOptions;
  };
  
  // Função para abrir o drawer
  const openDrawer = (placa) => {
    console.log('Placa clicada:', placa);
    // Sua lógica para abrir o drawer
  };
  </script>
  
  <style>
  /* Estilos adicionais se necessário */
  </style>