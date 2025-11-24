<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Salud Mental - Puerto Boyacá</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <nav class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-600 to-purple-800 rounded-lg flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Sistema de Salud Mental</h1>
                        <p class="text-xs text-gray-600">Puerto Boyacá</p>
                    </div>
                </div>
                <a href="/admin" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2.5 rounded-lg font-medium transition duration-200 shadow-md hover:shadow-lg">
                    Acceder al Sistema
                </a>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-20 left-10 w-72 h-72 bg-white rounded-full blur-3xl animate-float"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-white rounded-full blur-3xl animate-float" style="animation-delay: 2s;"></div>
        </div>

        <div class="container mx-auto px-6 relative z-10">
            <div class="max-w-4xl mx-auto text-center">
                <div class="inline-block bg-white/20 backdrop-blur-sm px-4 py-2 rounded-full mb-6">
                    <span class="text-sm font-medium">Secretaria General - Área de Sistemas</span>
                </div>

                <h1 class="text-5xl md:text-6xl font-bold mb-6 leading-tight">
                    Sistema de Gestión de<br/>
                    <span class="text-purple-200">Salud Mental</span>
                </h1>

                <p class="text-xl md:text-2xl text-purple-100 mb-8 max-w-2xl mx-auto">
                    Plataforma integral para el seguimiento y atención de casos de salud mental en Puerto Boyacá
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="/admin" class="bg-white text-purple-700 px-8 py-4 rounded-lg font-semibold hover:bg-purple-50 transition duration-200 shadow-xl">
                        Ingresar al Sistema
                    </a>
                    <a href="#caracteristicas" class="bg-purple-700/30 backdrop-blur-sm text-white px-8 py-4 rounded-lg font-semibold hover:bg-purple-700/40 transition duration-200 border-2 border-white/30">
                        Conocer Más
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Info Cards -->
    <section class="py-16 -mt-10">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover border-t-4 border-green-500">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Gestión Completa</h3>
                    <p class="text-gray-600">Control integral de pacientes, diagnósticos y seguimientos mensuales</p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 card-hover border-t-4 border-blue-500">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Seguro y Privado</h3>
                    <p class="text-gray-600">Sistema interno con control de acceso y roles de usuario</p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 card-hover border-t-4 border-purple-500">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Reportes y Análisis</h3>
                    <p class="text-gray-600">Estadísticas y exportación de datos para toma de decisiones</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Características -->
    <section id="caracteristicas" class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Características del Sistema</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Una plataforma completa para la gestión de casos de salud mental
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-12 max-w-6xl mx-auto">
                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Gestión de Pacientes</h3>
                        <p class="text-gray-600">Registro completo de información demográfica, antecedentes y asignación de casos a profesionales</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Intentos de Suicidio</h3>
                        <p class="text-gray-600">Registro detallado de eventos, factores de riesgo, mecanismos y seguimiento especializado</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Trastornos Mentales</h3>
                        <p class="text-gray-600">Gestión de diagnósticos CIE-10, tipos de ingreso, tratamientos y evolución de casos</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Consumo de SPA</h3>
                        <p class="text-gray-600">Control de sustancias psicoactivas, niveles de riesgo y programas de tratamiento</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Seguimientos Mensuales</h3>
                        <p class="text-gray-600">Registro cronológico de evolución, acciones realizadas y programación de próximas citas</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Control de Usuarios</h3>
                        <p class="text-gray-600">Sistema de roles y permisos con diferentes niveles de acceso según el perfil</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Instituciones -->
    <section class="py-20 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12">
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-bold text-gray-800 mb-4">Desarrollo e Implementación</h2>
                        <div class="w-20 h-1 bg-purple-600 mx-auto mb-6"></div>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-start gap-4 p-4 bg-purple-50 rounded-lg">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 mb-1">Alcaldía de Puerto Boyacá</h3>
                                <p class="text-gray-600">Secretaria de Desarrollo - Área de Salud</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 bg-blue-50 rounded-lg">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 mb-1">Hospital Jose Cayetano Vasquez</h3>
                                <p class="text-gray-600">Institución colaboradora en el seguimiento de casos</p>
                            </div>
                        </div>

                        <div class="mt-8 p-6 bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg border border-purple-200">
                            <div class="flex items-center gap-3 mb-3">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <h4 class="font-bold text-gray-800">Sistema de Uso Interno</h4>
                            </div>
                            <p class="text-gray-700">
                                Este sistema es de uso exclusivo para funcionarios autorizados de la Alcaldía de Puerto Boyacá
                                y del Hospital Jose Cayetano Vasquez. El acceso está restringido y protegido por controles de seguridad.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 gradient-bg text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 right-10 w-64 h-64 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 left-10 w-80 h-80 bg-white rounded-full blur-3xl"></div>
        </div>

        <div class="container mx-auto px-6 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-4xl font-bold mb-6">¿Eres funcionario autorizado?</h2>
                <p class="text-xl text-purple-100 mb-8">
                    Accede al sistema para gestionar casos y realizar seguimientos
                </p>
                <a href="/admin" class="inline-block bg-white text-purple-700 px-10 py-4 rounded-lg font-bold hover:bg-purple-50 transition duration-200 shadow-xl text-lg">
                    Acceder al Sistema
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-12">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-3 gap-8 mb-8">
                <div>
                    <h3 class="text-white font-bold text-lg mb-4">Sistema de Salud Mental</h3>
                    <p class="text-gray-400 text-sm">
                        Plataforma desarrollada para el seguimiento integral de casos de salud mental en Puerto Boyacá.
                    </p>
                </div>
                <div>
                    <h3 class="text-white font-bold text-lg mb-4">Contacto</h3>
                    <div class="space-y-2 text-sm">
                        <p class="text-gray-400">Alcaldía de Puerto Boyacá</p>
                        <p class="text-gray-400">Área de Sistemas</p>
                    </div>
                </div>
                <div>
                    <h3 class="text-white font-bold text-lg mb-4">Enlaces</h3>
                    <div class="space-y-2 text-sm">
                        <a href="/admin" class="block text-gray-400 hover:text-white transition">Acceder al Sistema</a>
                        <a href="#caracteristicas" class="block text-gray-400 hover:text-white transition">Características</a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 text-center text-sm text-gray-400">
                <p>&copy; {{ date('Y') }} Alcaldía de Puerto Boyacá. Sistema desarrollado bajo el Contrato 368 de 2025.</p>
                <p class="mt-2">Sistema de uso interno - Acceso restringido a funcionarios autorizados</p>
            </div>
        </div>
    </footer>
</body>
</html>
