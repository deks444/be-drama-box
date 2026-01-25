pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '5'))
        disableConcurrentBuilds()
    }

    stages {
        stage('Checkout') {
            steps {
                // Mengambil kode dari GitHub https://github.com/PTMajuJayaMakmur/drama-box-auth
                checkout scm
            }
        }

        stage('Environment Setup') {
            steps {
                echo 'Setting up .env file...'
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // Menangani spasi folder dengan tanda kutip ganda dan 'cat'
                        sh 'rm -rf .env'
                        sh 'cat "${SECRET_ENV}" > .env'
                        sh 'chmod 600 .env'
                    }
                }
            }
        }

        stage('Check/Install Runtime') {
            steps {
                echo 'Checking for PHP and Composer...'
                script {
                    // Berusaha menginstall PHP jika user jenkins punya akses sudo/apt
                    sh '''
                        if ! command -v php >/dev/null 2>&1; then
                            echo "PHP not found. Attempting to install..."
                            (apt-get update && apt-get install -y php-cli php-mbstring php-xml php-zip php-curl unzip git) || \
                            (sudo apt-get update && sudo apt-get install -y php-cli php-mbstring php-xml php-zip php-curl unzip git) || \
                            echo "Warning: Automatic install failed. Ensure PHP is installed on the host."
                        fi
                    '''
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                echo 'Installing Composer & Laravel Packages...'
                script {
                    // Download composer.phar secara lokal agar tidak bergantung pada system path
                    sh '''
                        curl -sS https://getcomposer.org/installer | php
                        php composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev
                    '''
                }
            }
        }

        stage('Laravel Preparation') {
            steps {
                echo 'Finalizing Laravel Setup...'
                sh '''
                    php artisan key:generate --force
                    php artisan storage:link
                    chmod -R 775 storage bootstrap/cache
                '''
            }
        }

        stage('Database & Optimization') {
            steps {
                echo 'Running Migrations and Caching...'
                sh '''
                    php artisan migrate --force
                    php artisan config:cache
                    php artisan route:cache
                    php artisan view:cache
                '''
            }
        }
    }

    post {
        success {
            echo '====================================='
            echo ' DEPLOYMENT DRAMA-BOX-AUTH SUCCESS!  '
            echo '====================================='
        }
        failure {
            echo '====================================='
            echo ' DEPLOYMENT FAILED! CHECK LOGS ABOVE '
            echo '====================================='
        }
    }
}
