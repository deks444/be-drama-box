pipeline {
    agent any

    triggers {
        githubPush()
    }

    options {
        buildDiscarder(logRotator(numToKeepStr: '5'))
        timeout(time: 15, unit: 'MINUTES')
        disableConcurrentBuilds()
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Setup PHP 8.4.14 (Official) & .env') {
            steps {
                echo 'Mengunduh PHP 8.4.14 dari Mirror Resmi...'
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // 1. Setup .env (Menangani folder dengan spasi)
                        sh 'rm -rf .env'
                        sh 'cat "${SECRET_ENV}" > .env'

                        // 2. Download PHP 8.4.14 Static Binary
                        sh '''
                            mkdir -p local_bin
                            
                            # Menggunakan link mirror resmi untuk PHP 8.4.14 CLI Linux x86_64
                            URL="https://dl.static-php.dev/static-php-cli/common/php-8.4.14-cli-linux-x86_64.tar.gz"
                            
                            curl -Lk "$URL" -o php.tar.gz
                            
                            # Ekstrak dan standarisasi nama binary ke 'php'
                            if tar -xzf php.tar.gz -C local_bin/; then
                                find local_bin -name "php*" -type f -exec mv {} local_bin/php \\;
                                chmod +x local_bin/php
                                echo "PHP 8.4.14 Berhasil Terpasang:"
                                ./local_bin/php -v
                            else
                                echo "ERROR: Gagal mengunduh PHP 8.4.14 dari mirror resmi (404)."
                                exit 1
                            fi
                        '''
                    }
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                echo 'Installing Composer & Laravel Packages...'
                sh '''
                    # Download composer.phar versi stabil
                    curl -Lk https://getcomposer.org/composer.phar -o composer.phar
                    
                    # Install dependensi (ignore-platform-reqs untuk fleksibilitas environment)
                    ./local_bin/php composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs
                '''
            }
        }

        stage('Laravel Preparation') {
            steps {
                echo 'Preparing Laravel Application...'
                sh '''
                    ./local_bin/php artisan key:generate --force
                    ./local_bin/php artisan storage:link
                    ./local_bin/php artisan migrate --force || echo "Migrasi gagal atau database belum siap."
                    ./local_bin/php artisan config:cache
                    ./local_bin/php artisan route:cache
                    chmod -R 775 storage bootstrap/cache || true
                '''
            }
        }

        stage('Serve on Port 9004') {
            steps {
                echo 'Menjalankan Laravel Server di port 9004...'
                script {
                    // Membersihkan proses lama di port 9004 agar tidak 'Address already in use'
                    sh 'fuser -k 9004/tcp || true'
                    
                    // Menjalankan server di background
                    // dontKillMe mencegah Jenkins membunuh server saat build selesai
                    sh '''
                        export JENKINS_NODE_COOKIE=dontKillMe
                        nohup ./local_bin/php artisan serve --host=0.0.0.0 --port=9004 > laravel_log.txt 2>&1 &
                    '''
                    echo 'Aplikasi berjalan di background (Port 9004)'
                }
            }
        }
    }

    post {
        always {
            echo 'Pembersihan file installer...'
            sh 'rm -f php.tar.gz composer.phar'
        }
        success {
            echo '==================================================='
            echo ' DEPLOY SUCCESS: DRAMA-BOX-AUTH RUNNING ON 9004    '
            echo ' PHP VERSION: 8.4.14                               '
            echo '==================================================='
        }
    }
}
