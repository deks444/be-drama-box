pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '5'))
        timeout(time: 20, unit: 'MINUTES')
    }

    stages {
        stage('Checkout SCM') {
            steps {
                // Mengambil kode dari GitHub
                checkout scm
            }
        }

        stage('Setup PHP & .env') {
            steps {
                echo 'Setting up Portable PHP and Environment...'
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // 1. Tangani masalah spasi folder dan file .env
                        sh 'rm -rf .env'
                        sh 'cat "${SECRET_ENV}" > .env'
                        sh 'chmod 600 .env'

                        // 2. Unduh Static PHP (8.2) karena sistem Anda tidak punya PHP
                        // Kita simpan di folder 'bin' lokal proyek
                        sh '''
                            mkdir -p local_bin
                            if [ ! -f local_bin/php ]; then
                                echo "Downloading static PHP binary..."
                                curl -Lo local_bin/php https://github.com/crazywhalecc/static-php-cli/releases/download/v1.6.4/php-8.2.16-micro-linux-x86_64
                                chmod +x local_bin/php
                            fi
                        '''
                    }
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                echo 'Installing Composer & Laravel Packages...'
                script {
                    // Jalankan composer menggunakan PHP portable yang baru diunduh
                    sh '''
                        curl -sS https://getcomposer.org/installer | ./local_bin/php
                        ./local_bin/php composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev
                    '''
                }
            }
        }

        stage('Laravel Preparation') {
            steps {
                echo 'Running Artisan commands...'
                sh '''
                    ./local_bin/php artisan key:generate --force
                    ./local_bin/php artisan storage:link
                    chmod -R 775 storage bootstrap/cache
                '''
            }
        }

        stage('Database & Optimization') {
            steps {
                echo 'Migrating and Caching...'
                sh '''
                    # Pastikan koneksi DB di .env sudah benar sebelum migrasi
                    ./local_bin/php artisan migrate --force
                    ./local_bin/php artisan config:cache
                    ./local_bin/php artisan route:cache
                    ./local_bin/php artisan view:cache
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
