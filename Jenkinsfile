pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '5'))
        disableConcurrentBuilds()
        timeout(time: 15, unit: 'MINUTES')
    }

    stages {
        stage('Checkout') {
            steps {
                // Mengambil kode dari GitHub
                checkout scm
            }
        }

        stage('Setup Environment & Portable PHP') {
            steps {
                echo 'Configuring .env and downloading runtime...'
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // Menangani spasi pada path dan kredensial .env
                        sh 'rm -rf .env'
                        sh 'cat "${SECRET_ENV}" > .env'

                        // Mengunduh PHP Static Binary yang stabil (v8.2.16)
                        // Menggunakan -L untuk mengikuti redirect GitHub
                        sh '''
                            mkdir -p local_bin
                            echo "Downloading PHP binary..."
                            curl -L "https://github.com/crazywhalecc/static-php-cli/releases/download/v1.6.4/php-8.2.16-cli-linux-x86_64.tar.gz" -o php.tar.gz
                            
                            # Ekstrak file dan pastikan binary bisa dijalankan
                            tar -xzf php.tar.gz -C local_bin/
                            chmod +x local_bin/php
                            
                            # Test eksekusi PHP
                            ./local_bin/php -v
                        '''
                    }
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                echo 'Installing Composer and Laravel Packages...'
                sh '''
                    # Download installer composer
                    curl -sS https://getcomposer.org/installer -o composer-setup.php
                    
                    # Install composer lokal
                    ./local_bin/php composer-setup.php
                    
                    # Install dependensi Laravel (Ignore platform reqs karena env terbatas)
                    ./local_bin/php composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs
                '''
            }
        }

        stage('Laravel Finalization') {
            steps {
                echo 'Optimizing Laravel Application...'
                sh '''
                    # Jalankan artisan menggunakan PHP portable
                    ./local_bin/php artisan key:generate --force
                    ./local_bin/php artisan storage:link
                    
                    # Jalankan migrasi jika database sudah terhubung di .env
                    ./local_bin/php artisan migrate --force || echo "Migrasi dilewati atau gagal."
                    
                    # Optimasi cache
                    ./local_bin/php artisan config:cache
                    ./local_bin/php artisan route:cache
                    ./local_bin/php artisan view:cache
                    
                    # Set izin folder (tidak akan gagal jika permission denied)
                    chmod -R 775 storage bootstrap/cache || true
                '''
            }
        }
    }

    post {
        always {
            echo 'Cleaning up temporary files...'
            sh 'rm -f php.tar.gz composer-setup.php composer.phar'
        }
        success {
            echo '============================================='
            echo ' DEPLOYMENT DRAMA-BOX-AUTH SUCCESSFUL!       '
            echo '============================================='
        }
        failure {
            echo '============================================='
            echo ' DEPLOYMENT FAILED. CHECK CONSOLE LOGS.      '
            echo '============================================='
        }
    }
}
