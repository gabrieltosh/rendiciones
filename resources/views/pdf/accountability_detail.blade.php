<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Asiento Contable</title>
    <style>
        @page {
            margin: 25px 35px 50px 35px;
        }
        @font-face {
            font-family: 'Poppins';
            src: url('{{ storage_path('fonts/Poppins-Regular.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Poppins';
            src: url('{{ storage_path('fonts/Poppins-Italic.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: italic;
        }

        @font-face {
            font-family: 'Poppins';
            src: url('{{ storage_path('fonts/Poppins-ExtraLight.ttf') }}') format('truetype');
            font-weight: 200;
            font-style: normal;
        }

        @font-face {
            font-family: 'Poppins';
            src: url('{{ storage_path('fonts/Poppins-Light.ttf') }}') format('truetype');
            font-weight: 300;
            font-style: normal;
        }

        @font-face {
            font-family: 'Poppins';
            src: url('{{ storage_path('fonts/Poppins-Regular.ttf') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
        }

        @font-face {
            font-family: 'Poppins';
            src: url('{{ storage_path('fonts/Poppins-Medium.ttf') }}') format('truetype');
            font-weight: 500;
            font-style: bold;
        }

        * {
            font-family: "Poppins", sans-serif;
        }

        .text-subtitle {
            font-family: "Poppins", sans-serif;
            font-size: 11px;
            padding: 0px;
            margin: 0px;
            font-weight: 200;
            font-style: normal;
        }

        .text-detail {
            font-family: "Poppins", sans-serif;
            font-weight: 400;
            font-style: normal;
            font-size: 12px;
        }

        .title-detail {
            font-family: "Poppins", sans-serif;
            font-weight: 400;
            font-style: normal;
            font-size: 13px;
        }
        .text-document {
            font-family: "Poppins", sans-serif;
            font-weight: 400;
            font-style: normal;
            font-size: 11px;
        }
        .title-document {
            font-family: "Poppins", sans-serif;
            font-weight: 200;
            font-style: normal;
            font-size: 10px;
        }
        .table-border-md,
        .table-border-md td,
        .table-border-md th {
            border: 0.2px solid black;
            border-collapse: collapse;
            height: 17px !important;
        }
    </style>
</head>

<body>
    <table width="100%">
        <tr>
            <td align="left" style="width: 15%">
                <img src="{{ storage_path('app/public/images/'.$company['logo']) }}" width="80">
            </td>
            <td align="left" style="width:55%">
                <table>
                    <tr style="font-size: 11px;">
                        <td>
                            <strong>{{$company['company_name']}}</strong>
                        </td>
                    </tr>
                    <tr style="font-size: 11px;">
                        <td>
                            <strong>{{$company['company_location']}}</strong>
                        </td>
                    </tr>
                    <tr style="font-size: 11px;">
                        <td>
                            <strong>
                                NIT: {{$company['nit']}}
                            </strong>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="font-size: 12px;width: 15%;padding:0px;margin:0px" align="right">
                <table>
                    <tr style="font-size: 11px;">
                        <td>
                            <strong>Número Origen:</strong>
                        </td>
                    </tr>
                    <tr style="font-size: 11px;">
                        <td>
                            <strong>Fecha Reporte:</strong>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="font-size: 12px;width: 15%;padding:0px;margin:0px" align="left">
                <table>
                    <tr style="font-size: 11px;">
                        <td>
                            {{$data->id}}
                        </td>
                    </tr>
                    <tr style="font-size: 11px;">
                        <td>
                            {{ Carbon\Carbon::now()->format('d-m-Y') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <br>
    <table width="100%">
        <tr>
            <td align="left" style="width: 70%;font-size: 13px;font-weight: 400;">
                {{$data->description}}
            </td>
            <td tyle="width: 70%;"></td>
        </tr>
    </table>
    <div style="border-bottom: 0.5px rgba(0, 0, 0, 0.3) solid;margin: 10px 0px"></div>
    <table width="100%" style="margin-top: 5px">
        <tr>
            <td style="width: 33%;">
                <div class="text-subtitle">
                    REALIZADO POR:
                </div>
                <div class="text-detail">{{$data->user->name}}</div>
            </td>
            <td style="width: 33%;font-size: 12px;">
                <div class="text-subtitle">
                    EMPLEADO:
                </div>
                <div class="text-detail">{{$data->employee_code.' - '.$data->employee_name}}</div>
            </td>
            <td style="width: 33%;font-size: 12px;">
                <div class="text-subtitle">
                    CUENTA:
                </div>
                <div class="text-detail">{{$data->account_name}}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 33%;">
                <div class="text-subtitle">
                    MONTO:
                </div>
                <div class="text-detail"> {{$data->profile->type_currency.' '.$data->total}}</div>
            </td>
            <td style="width: 33%;">
                <div class="text-subtitle">
                    FECHA INICIO:
                </div>
                <div class="text-detail">{{ Carbon\Carbon::parse($data->start_date)->format('d-m-Y') }}</div>
            </td>
            <td style="width: 33%;">
                <div class="text-subtitle">
                    FECHA FINAL:
                </div>
                <div class="text-detail">{{ Carbon\Carbon::parse($data->end_date)->format('d-m-Y') }}</div>
            </td>
        </tr>
    </table>
    <div style="border-bottom: 0.5px rgba(0, 0, 0, 0.3) solid;margin: 10px 0px 15px 0px"></div>
    <table width="100%">
        <tr>
            <td align="left" style="width: 70%;" class="title-detail">
                DETALLE DE DOCUMENTOS
            </td>
            <td tyle="width: 70%;"></td>
        </tr>
    </table>
    <div style="border-bottom: 0.5px rgba(0, 0, 0, 0.3) solid;margin: 15px 0px 0 0px"></div>
    @foreach ($data->detail as $item)
    <table width="100%" style="margin-top: 5px;padding:15px">
        <tr>
            <td style="width: 33%;">
                <div class="title-document">
                    CUENTA:
                </div>
                <div class="text-document">{{$item->account_name}}</div>
            </td>
            <td style="width: 33%;font-size: 12px;">
                <div class="title-document">
                    FECHA:
                </div>
                <div class="text-document">{{ Carbon\Carbon::parse($item->date)->format('d-m-Y') }}</div>
            </td>
            <td style="width: 33%;font-size: 12px;">
                <div class="title-document">
                    TIPO DOCUMENTO:
                </div>
                <div class="text-document">{{$item->document->name}}</div>
            </td>
            <td style="width: 33%;">
                <div class="title-document">
                    CONCEPTO:
                </div>
                <div class="text-document">{{$item->concept}}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 33%;">
                <div class="title-document">
                    Nº DOCUMENTO
                </div>
                <div class="text-document">{{$item->document_number?$item->document_number:'-'}}</div>
            </td>
            <td style="width: 33%;">
                <div class="title-document">
                    Nº AUTORIZACIÓN
                </div>
                <div class="text-document">{{$item->authorization_number?$item->authorization_number:'-'}}</div>
            </td>
            <td style="width: 33%;">
                <div class="title-document">
                    CUF
                </div>
                <div class="text-document">{{$item->cuf?$item->cuf:'-'}}</div>
            </td>
            <td style="width: 33%;">
                <div class="title-document">
                    CODIGO DE CONTROL
                </div>
                <div class="text-document">{{$item->control_code?$item->control_code:'-'}}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 33%;">
                <div class="title-document">
                   RAZON SOCIAL
                </div>
                <div class="text-document">{{$item->business_name?$item->business_name:'-'}}</div>
            </td>
            <td style="width: 33%;">
                <div class="title-document">
                    NIT
                </div>
                <div class="text-document">{{$item->nit?$item->nit:'-'}}</div>
            </td>
            <td style="width: 33%;">
                <div class="title-document">
                    PROYECTO
                </div>
                <div class="text-document">{{$item->project_code?$item->project_code:'-'}}</div>
            </td>
            <td style="width: 33%;">
                <div class="title-document">
                    NORMA DE REPARTO 1
                </div>
                <div class="text-document">{{$item->distribution_rule_one?$item->distribution_rule_one:'-'}}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 33%;">
                <div class="title-document">
                   NORMA DE REPARTO 2
                </div>
                <div class="text-document">{{$item->distribution_rule_second?$item->distribution_rule_second:'-'}}</div>
            </td>
            <td style="width: 33%;">
                <div class="title-document">
                    NORMA DE REPARTO 3
                </div>
                <div class="text-document">{{$item->distribution_rule_three?$item->distribution_rule_three:'-'}}</div>
            </td>
            <td style="width: 33%;">
                <div class="title-document">
                    NORMA DE REPARTO 4
                </div>
                <div class="text-document">{{$item->distribution_rule_four?$item->distribution_rule_four:'-'}}</div>
            </td>
            <td style="width: 33%;">
                <div class="title-document">
                    NORMA DE REPARTO 5
                </div>
                <div class="text-document">{{$item->distribution_rule_five?$item->distribution_rule_five:'-'}}</div>
            </td>
        </tr>
        <tr>
            <td colspan="4">
                <table width="100%" >
                    <tr>
                        <td colspan="7">
                            <div style="border-bottom: 0.5px rgba(0, 0, 0, 0.3) solid;"></div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" class="title-document">Importe</td>
                        <td align="center" class="title-document">Descuento</td>
                        <td align="center" class="title-document">Gift Card</td>
                        <td align="center" class="title-document">Tasa Cero</td>
                        <td align="center" class="title-document">Excento</td>
                        <td align="center" class="title-document">Tasa</td>
                        <td align="center" class="title-document">Ice</td>
                    </tr>
                    <tr>
                        <td colspan="7">
                            <div style="border-bottom: 0.5px rgba(0, 0, 0, 0.3) solid;"></div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" class="text-document">{{number_format($item->amount,2)}}</td>
                        <td align="center" class="text-document">{{number_format($item->discount,2)}}</td>
                        <td align="center" class="text-document">{{number_format($item->gift_card,2)}}</td>
                        <td align="center" class="text-document">{{number_format($item->rate_zero,2)}}</td>
                        <td align="center" class="text-document">{{number_format($item->excento,2)}}</td>
                        <td align="center" class="text-document">{{number_format($item->rate,2)}}</td>
                        <td align="center" class="text-document">{{number_format($item->rate,2)}}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div style="border-bottom: 0.5px rgba(0, 0, 0, 0.3) solid;"></div>
    @endforeach
    <script type="text/php">
        if (isset($pdf)) {
            $x = 550;
            $y = 760;
            $text = "Pag. {PAGE_NUM} de {PAGE_COUNT}";
            $font = $fontMetrics->getFont("Times New Roman");
            $size = 7;
            $color = array(0,0,0);
            $word_space = 0.0;  //  default
            $char_space = 0.0;  //  default
            $angle = 0.0;   //  default
            $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
        }
    </script>
</body>
</html>
