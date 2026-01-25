pipeline {
    agent {
        // Menggunakan Docker agar Jenkins tidak perlu install PHP secara manual
        docker {
            image 'php:8.2-cli' // Sesuaikan dengan versi Laravel Anda (8.1/8.2/8.3)
            args '-u root'      // Menjalankan sebagai root agar tidak ada masalah izin file
        }
    }

    options {
        buildDiscarder(logRotator(numToKeepStr: '5'))
        disableConcurrentBuilds()
        timeout(time: 15, unit: 'MINUTES')
    }

    stages {
        stage('Initialize') {
            steps {
                echo 'Installing system dependencies...'
                // Laravel memerlukan unzip dan git untuk composer
                sh 'apt-get update && apt-get install -y unzip git libpng-dev libonig-dev libxml2-dev'
            }
        }

        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Environment Setup') {
            steps {
                echo 'Setting up .env file...'
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // Menangani spasi pada path dengan kutip ganda
                        sh 'rm -rf .env'
                        sh 'cat "${SECRET_ENV}" > .env'
                        sh 'chmod 600 .env'
                    }
                }
            }
        }

        stage('Install Composer') {
            steps {
                echo 'Installing Composer and Dependencies...'
                script {
                    // Download composer lokal karena di image php:cli biasanya belum ada
                    sh 'curl -sS https://getcomposer.org/installer | php'
                    sh 'php composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev'
                }
            }
        }

        stage('Laravel Preparation') {
            steps {
                echo 'Optimizing Laravel...'
                script {
                    // Pastikan folder-folder penting tersedia
                    sh 'mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache'
                    sh 'php artisan key:generate --force'
                    sh 'php artisan storage:link'
                    sh 'chmod -R 777 storage bootstrap/cache'
                }
            }
        }

        stage('Database Migration') {
            steps {
                echo 'Running Migrations...'
                // Gunakan --force untuk produksi
                sh 'php artisan migrate --force'
            }
        }

        stage('Cache & Optimize') {
            steps {
                sh 'php artisan config:cache'
                sh 'php artisan route:cache'
                sh 'php artisan view:cache'
            }
        }
    }

    post {
        success {
            echo 'Drama Box Auth Deployed Successfully!'
        }
        failure {
            echo 'Deployment Failed. Check the logs above.'
        }
    }
}
